"""Publish verified artifacts; a retry may resume this commit's draft, never replace it."""
from pathlib import Path
from tempfile import TemporaryDirectory
import hashlib
import json
import os
import re
import subprocess

from release import ROOT, validate_version

REPO = 'abel-hechavarria/herizon-cars'


def gh(*args):
    result = subprocess.run(['gh', *args], capture_output=True, text=True, timeout=60)
    if result.returncode:
        raise RuntimeError('GitHub operation failed: ' + ' '.join(args[:3]))
    return result.stdout


def publish():
    version = validate_version()
    tag = 'v' + version
    commit = os.environ.get('GITHUB_SHA', '')
    if os.environ.get('GITHUB_REPOSITORY') != REPO or os.environ.get('GITHUB_REF_NAME') != tag or not re.fullmatch('[a-f0-9]{40}', commit):
        raise ValueError('Publishing is restricted to this repository and an explicit version tag')
    archive = ROOT / 'build' / f'herizon-cars-{version}.zip'
    sums = ROOT / 'build/SHA256SUMS'
    notes = ROOT / 'build/RELEASE_NOTES.md'
    if not archive.is_file() or not sums.is_file() or not notes.is_file():
        raise ValueError('Build the release package and notes first')
    expected_sum = hashlib.sha256(archive.read_bytes()).hexdigest() + '  ' + archive.name + '\n'
    if sums.read_text(encoding='utf-8') != expected_sum:
        raise ValueError('Package checksum differs')
    # The by-tag endpoint omits unpublished drafts. The authenticated list includes them.
    route = 'repos/' + REPO + '/releases?per_page=100'
    def find_release():
        releases = json.loads(gh('api', route))
        if not isinstance(releases, list):
            raise ValueError('Invalid releases response')
        matches = [item for item in releases if item.get('tag_name') == tag]
        if len(matches) > 1:
            raise ValueError('Multiple releases use the same tag; inspect before retrying')
        return matches[0] if matches else None
    data = find_release()
    if data is None:
        gh('release', 'create', tag, '--repo', REPO, '--verify-tag', '--target', commit,
           '--title', 'Herizon Cars ' + version, '--notes-file', str(notes), '--draft')
        data = find_release()
    if not isinstance(data, dict):
        raise ValueError('Draft not found after creation; preserve state and retry')
    if data.get('draft') is not True or data.get('target_commitish') != commit or data.get('tag_name') != tag:
        raise ValueError('Refusing to modify a published release or another commit\'s draft')
    if data.get('body', '').strip() != notes.read_text(encoding='utf-8').strip():
        raise ValueError('Existing draft notes differ; inspect before retrying')
    assets = {asset['name']: asset for asset in data.get('assets', [])}
    with TemporaryDirectory() as folder:
        for file in [archive, sums]:
            if file.name in assets:
                gh('release', 'download', tag, '--repo', REPO, '--pattern', file.name, '--dir', folder)
                if hashlib.sha256((Path(folder) / file.name).read_bytes()).digest() != hashlib.sha256(file.read_bytes()).digest():
                    raise ValueError('Existing draft asset differs: ' + file.name)
            else:
                gh('release', 'upload', tag, str(file), '--repo', REPO)
    gh('release', 'edit', tag, '--repo', REPO, '--draft=false', '--latest')
    print('Published ' + tag)


if __name__ == '__main__':
    publish()
