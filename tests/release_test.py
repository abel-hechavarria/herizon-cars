"""Exercise delivery boundaries without touching the checkout or WordPress."""
from pathlib import Path
from tempfile import TemporaryDirectory
from unittest.mock import patch
from zipfile import ZipFile
import contextlib
import io
import importlib.util
import json
import os
import shutil
import sys
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
import release
import package
spec = importlib.util.spec_from_file_location('publisher', release.ROOT / 'scripts/publish-release.py')
publisher = importlib.util.module_from_spec(spec)
spec.loader.exec_module(publisher)


class DeliveryTests(unittest.TestCase):
    def setUp(self):
        self.temp = TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        for name in ['style.css', 'package.json', 'readme.txt', 'CHANGELOG.md', 'inc', 'dist',
                     'functions.php', 'LICENSE', 'THIRD-PARTY-NOTICES.txt', 'blocks', 'assets',
                     'template-parts', 'languages']:
            source = release.ROOT / name
            if source.is_file():
                shutil.copyfile(source, self.root / name)
            else:
                shutil.copytree(source, self.root / name)
        self.addCleanup(patch.stopall)
        patch.object(release, 'ROOT', self.root).start()
        patch.object(package, 'ROOT', self.root).start()
        patch.dict(os.environ, {'GITHUB_REF_TYPE': 'branch', 'GITHUB_REF_NAME': 'main'}).start()

    def test_release_marker_and_version(self):
        version = release.validate_version()
        body = release.notes()
        self.assertIn('<!-- herizon-cars-update {"requires":"6.5","requires_php":"8.2"} -->', body)
        self.assertIn(f'herizon-cars-{version}.zip', body)

    def test_mismatched_tag(self):
        with patch.dict(os.environ, {'GITHUB_REF_TYPE': 'tag', 'GITHUB_REF_NAME': 'v0.0.1'}):
            with self.assertRaises(ValueError):
                release.validate_version()

    def test_mismatched_source_version(self):
        source = self.root / 'style.css'
        source.write_text(source.read_text().replace('Version: ' + release.validate_version(), 'Version: 0.0.0'))
        with self.assertRaises(ValueError):
            release.validate_version()

    def test_missing_changelog(self):
        (self.root / 'CHANGELOG.md').write_text('# Empty')
        with self.assertRaises(ValueError):
            release.notes()

    def test_archive_scope_structure_and_reproducibility(self):
        (self.root / '.env').write_text('private-fixture')
        (self.root / 'node_modules').mkdir()
        (self.root / 'node_modules/private.txt').write_text('not for runtime')
        with contextlib.redirect_stdout(io.StringIO()):
            path = package.package()
            first = path.read_bytes()
            package.package()
        self.assertEqual(first, path.read_bytes())
        with ZipFile(path) as archive:
            names = archive.namelist()
            self.assertIn('herizon-cars/style.css', names)
            self.assertIn('herizon-cars/dist/.vite/manifest.json', names)
            self.assertIn('herizon-cars/inc/integrations/github-updates.php', names)
            self.assertFalse(any(x in name for name in names for x in ['node_modules', '.env', '/tests/', '/docs/', '/scripts/', '.git/']))
        self.assertIn(path.name, (self.root / 'build/SHA256SUMS').read_text())

    def test_missing_compiled_asset_rejects_package(self):
        source = self.root / 'dist/.vite/manifest.json'
        data = json.loads(source.read_text())
        next(iter(data.values()))['file'] = 'missing.js'
        source.write_text(json.dumps(data))
        with self.assertRaises(ValueError):
            package.package()

    def test_manifest_path_traversal_rejects_package(self):
        source = self.root / 'dist/.vite/manifest.json'
        source.write_text(json.dumps({'bad': {'file': '../style.css'}}))
        with self.assertRaises(ValueError):
            package.package()


class PublishTests(unittest.TestCase):
    def setUp(self):
        self.temp = TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        build = self.root / 'build'
        build.mkdir()
        self.archive = build / 'herizon-cars-1.2.0.zip'
        self.archive.write_bytes(b'fixture-archive')
        import hashlib
        (build / 'SHA256SUMS').write_text(hashlib.sha256(self.archive.read_bytes()).hexdigest() + '  ' + self.archive.name + '\n')
        (build / 'RELEASE_NOTES.md').write_text('fixture-notes\n')
        self.data = {'id': 42, 'draft': True, 'target_commitish': 'a' * 40, 'tag_name': 'v1.2.0', 'body': 'fixture-notes', 'assets': []}
        self.listed = True
        self.remote_commit = 'a' * 40
        self.calls = []
        self.downloads = {}
        self.upload_failure = False
        self.addCleanup(patch.stopall)
        patch.object(publisher, 'ROOT', self.root).start()
        patch.object(publisher, 'validate_version', return_value='1.2.0').start()
        patch.dict(os.environ, {'GITHUB_REPOSITORY': publisher.REPO, 'GITHUB_REF_NAME': 'v1.2.0', 'GITHUB_SHA': 'a' * 40}).start()
        patch.object(publisher, 'gh', side_effect=self.gh).start()

    def gh(self, *args, **kwargs):
        self.calls.append(args)
        route = args[1]
        if '/commits/' in route:
            return json.dumps({'sha': self.remote_commit})
        if '/releases?' in route:
            return json.dumps([self.data] if self.listed else [])
        if args[-2:] == ('--method', 'POST'):
            raise AssertionError('Unexpected request')
        if '/assets?name=' in route:
            if self.upload_failure:
                raise RuntimeError('Fixture upload failure')
            file = Path(args[-1])
            asset_id = 100 + len(self.downloads)
            self.downloads[asset_id] = file.read_bytes()
            return json.dumps({'id': asset_id, 'name': file.name, 'state': 'uploaded', 'size': file.stat().st_size})
        if '/releases/assets/' in route:
            self.assertTrue(kwargs.get('binary'))
            return self.downloads[int(route.rsplit('/', 1)[1])]
        if '--method' in args and args[args.index('--method') + 1] == 'POST':
            return json.dumps(self.data)
        if '--method' in args and args[args.index('--method') + 1] == 'PATCH':
            self.assertTrue(route.endswith('/releases/42'))
            return json.dumps(self.data | {'draft': False})
        raise AssertionError('Unexpected GitHub request: ' + str(args))

    def run_publish(self):
        with contextlib.redirect_stdout(io.StringIO()):
            publisher.publish()

    def mutations(self):
        return [c for c in self.calls if '--method' in c]

    def test_resume_matching_draft(self):
        self.run_publish()
        self.assertEqual(len(self.mutations()), 3)  # two uploads, then publish
        self.assertFalse(any(c[1].endswith('/releases') for c in self.mutations()))

    def test_create_uses_response_id_without_rediscovery(self):
        self.listed = False
        self.run_publish()
        self.assertEqual(len([c for c in self.calls if '/releases?' in c[1]]), 1)
        self.assertEqual(len(self.mutations()), 4)

    def test_published_or_other_commit_preserved(self):
        original = self.data.copy()
        for change in [{'draft': False}, {'target_commitish': 'b' * 40}, {'body': 'different'}]:
            self.data = original | change
            self.calls.clear()
            with self.subTest(change=change), self.assertRaises(ValueError):
                self.run_publish()
            self.assertEqual(self.mutations(), [])

    def test_wrong_repository_rejected_before_network(self):
        with patch.dict(os.environ, {'GITHUB_REPOSITORY': 'someone/else'}), self.assertRaises(ValueError):
            self.run_publish()
        self.assertEqual(self.calls, [])

    def test_conflicting_existing_asset_preserved(self):
        self.data['assets'] = [{'id': 77, 'name': self.archive.name, 'state': 'uploaded', 'size': self.archive.stat().st_size}]
        self.downloads[77] = b'other-archive'
        with self.assertRaisesRegex(ValueError, 'asset differs'):
            self.run_publish()
        self.assertEqual(self.mutations(), [])

    def test_wrong_remote_tag_commit_rejected(self):
        self.remote_commit = 'b' * 40
        with self.assertRaises(ValueError):
            self.run_publish()
        self.assertEqual(self.mutations(), [])

    def test_invalid_release_or_asset_id_rejected(self):
        self.data['id'] = 'https://evil.invalid'
        with self.assertRaisesRegex(ValueError, 'release ID'):
            self.run_publish()
        self.data['id'] = 42
        self.data['assets'] = [{'id': -1, 'name': self.archive.name, 'state': 'uploaded', 'size': self.archive.stat().st_size}]
        with self.assertRaisesRegex(ValueError, 'Invalid or incomplete'):
            self.run_publish()
        self.assertEqual(self.mutations(), [])

    def test_upload_failure_preserves_draft(self):
        self.upload_failure = True
        with self.assertRaises(RuntimeError):
            self.run_publish()
        self.assertFalse(any('PATCH' in c for c in self.calls))

    def test_existing_matching_assets_are_not_replaced(self):
        for index, file in enumerate([self.archive, self.root / 'build/SHA256SUMS'], 77):
            self.data['assets'].append({'id': index, 'name': file.name, 'state': 'uploaded', 'size': file.stat().st_size})
            self.downloads[index] = file.read_bytes()
        self.run_publish()
        self.assertEqual(len(self.mutations()), 1)
        self.assertIn('PATCH', self.mutations()[0])


if __name__ == '__main__':
    unittest.main()
