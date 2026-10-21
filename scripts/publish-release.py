"""Publish verified artifacts; a retry may resume this commit's draft, never replace it."""
from pathlib import Path
import hashlib
import json
import os
import re
import subprocess

from release import ROOT, validate_version

REPO = 'abel-hechavarria/herizon-cars'


def gh(*args, binary=False):
    result = subprocess.run(['gh', *args], capture_output=True, text=not binary, timeout=60)
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
    # Resolve annotated or lightweight remote tags; POST alone does not verify an existing tag.
    base = 'repos/' + REPO
    remote_commit = json.loads(gh('api', base + '/commits/' + tag))
    if remote_commit.get('sha') != commit:
        raise ValueError('Remote tag does not resolve to this workflow commit')
    # REST by-tag does not resolve pending drafts. List only to resume an existing draft.
    def find_release():
        matches = []
        for page in range(1, 21):
            releases = json.loads(gh('api', base + f'/releases?per_page=100&page={page}'))
            if not isinstance(releases, list):
                raise ValueError('Invalid releases response')
            matches.extend(item for item in releases if item.get('tag_name') == tag)
            if len(releases) < 100:
                if len(matches) > 1:
                    raise ValueError('Multiple releases use the same tag; inspect before retrying')
                return matches[0] if matches else None
        raise ValueError('Release listing exceeds recovery scan limit; inspect before publishing')
    data = find_release()
    if data is None:
        # Retain the creation response ID; never depend on rediscovering a new draft.
        data = json.loads(gh('api', base + '/releases', '--method', 'POST',
            '-f', 'tag_name=' + tag, '-f', 'target_commitish=' + commit,
            '-f', 'name=Herizon Cars ' + version, '-F', 'body=@' + str(notes),
            '-F', 'draft=true', '-F', 'prerelease=false'))
    if not isinstance(data, dict):
        raise ValueError('Invalid draft response')
    release_id = data.get('id')
    if type(release_id) is not int or release_id <= 0:
        raise ValueError('Invalid release ID')
    if data.get('draft') is not True or data.get('target_commitish') != commit or data.get('tag_name') != tag:
        raise ValueError('Refusing to modify a published release or another commit\'s draft')
    if data.get('body', '').strip() != notes.read_text(encoding='utf-8').strip():
        raise ValueError('Existing draft notes differ; inspect before retrying')
    assets = {asset['name']: asset for asset in data.get('assets', [])}
    if not set(assets).issubset({archive.name, sums.name}):
        raise ValueError('Draft contains unexpected assets')
    for file in [archive, sums]:
        asset = assets.get(file.name)
        if asset is None:
            upload = 'https://uploads.github.com/' + base + f'/releases/{release_id}/assets?name=' + file.name
            asset = json.loads(gh('api', upload, '--method', 'POST', '--header',
                'Content-Type: application/octet-stream', '--input', str(file)))
        asset_id = asset.get('id')
        if type(asset_id) is not int or asset_id <= 0 or asset.get('state') != 'uploaded' or asset.get('name') != file.name or asset.get('size') != file.stat().st_size:
            raise ValueError('Invalid or incomplete draft asset: ' + file.name)
        downloaded = gh('api', base + f'/releases/assets/{asset_id}', '--header', 'Accept: application/octet-stream', binary=True)
        if hashlib.sha256(downloaded).digest() != hashlib.sha256(file.read_bytes()).digest():
            raise ValueError('Existing draft asset differs: ' + file.name)
    published = json.loads(gh('api', base + f'/releases/{release_id}', '--method', 'PATCH', '-F', 'draft=false', '-f', 'make_latest=true'))
    if published.get('id') != release_id or published.get('tag_name') != tag or published.get('draft') is not False:
        raise ValueError('Unexpected publication response; inspect remote state')
    print('Published ' + tag)


if __name__ == '__main__':
    publish()
