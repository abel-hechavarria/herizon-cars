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
        self.data = {'draft': True, 'target_commitish': 'a' * 40, 'tag_name': 'v1.2.0', 'body': 'fixture-notes', 'assets': []}
        self.addCleanup(patch.stopall)
        patch.object(publisher, 'ROOT', self.root).start()
        patch.object(publisher, 'validate_version', return_value='1.2.0').start()
        patch.dict(os.environ, {'GITHUB_REPOSITORY': publisher.REPO, 'GITHUB_REF_NAME': 'v1.2.0', 'GITHUB_SHA': 'a' * 40}).start()

    def run_publish(self):
        with contextlib.redirect_stdout(io.StringIO()):
            publisher.publish()

    def test_resume_matching_draft(self):
        with patch.object(publisher, 'gh', return_value=json.dumps(self.data)) as mock:
            self.run_publish()
        commands = [c.args for c in mock.call_args_list]
        self.assertEqual(sum(c[:2] == ('release', 'upload') for c in commands), 2)
        self.assertEqual(commands[-1][:2], ('release', 'edit'))

    def test_initial_draft_created_before_publish(self):
        with patch.object(publisher, 'gh', side_effect=[None, '', json.dumps(self.data), '', '', '']) as mock:
            self.run_publish()
        self.assertEqual(mock.call_args_list[1].args[:2], ('release', 'create'))

    def test_published_or_other_commit_preserved(self):
        for change in [{'draft': False}, {'target_commitish': 'b' * 40}, {'body': 'different'}]:
            with self.subTest(change=change), patch.object(publisher, 'gh', return_value=json.dumps(self.data | change)) as mock:
                with self.assertRaises(ValueError):
                    self.run_publish()
                self.assertEqual(mock.call_count, 1)

    def test_wrong_repository_rejected_before_network(self):
        with patch.dict(os.environ, {'GITHUB_REPOSITORY': 'someone/else'}), patch.object(publisher, 'gh') as mock:
            with self.assertRaises(ValueError):
                self.run_publish()
            mock.assert_not_called()

    def test_conflicting_existing_asset_preserved(self):
        self.data['assets'] = [{'name': self.archive.name}]
        def call(*args, **kwargs):
            if args[0] == 'api':
                return json.dumps(self.data)
            if args[:2] == ('release', 'download'):
                (Path(args[-1]) / self.archive.name).write_bytes(b'other-archive')
                return ''
            raise AssertionError('No upload or publish after conflict')
        with patch.object(publisher, 'gh', side_effect=call):
            with self.assertRaisesRegex(ValueError, 'asset differs'):
                self.run_publish()


if __name__ == '__main__':
    unittest.main()
