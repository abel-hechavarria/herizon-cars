"""Validate release versions and generate the metadata consumed by WordPress."""
from pathlib import Path
import argparse
import json
import os
import re

ROOT = Path(__file__).resolve().parents[1]


def header(name):
    match = re.search(r'^' + re.escape(name) + r':\s*(.+)$', (ROOT / 'style.css').read_text(encoding='utf-8'), re.M)
    if not match:
        raise ValueError(f'Missing theme header: {name}')
    return match[1].strip()


def validate_version():
    version = json.loads((ROOT / 'package.json').read_text(encoding='utf-8'))['version']
    if not re.fullmatch(r'(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)', version):
        raise ValueError('Only stable X.Y.Z versions can be released')
    if version != header('Version'):
        raise ValueError('package.json and style.css versions differ')
    bootstrap = (ROOT / 'inc/bootstrap.php').read_text(encoding='utf-8')
    if f"define('HERIZON_CARS_VERSION', '{version}');" not in bootstrap:
        raise ValueError('Bootstrap version differs')
    if not (ROOT / 'readme.txt').read_text(encoding='utf-8').startswith(f'Herizon Cars {version}\n'):
        raise ValueError('readme.txt version differs')
    if os.environ.get('GITHUB_REF_TYPE') == 'tag' and os.environ.get('GITHUB_REF_NAME') != 'v' + version:
        raise ValueError('Release tag must exactly match the source version')
    return version


def notes():
    version = validate_version()
    meta = {'requires': header('Requires at least'), 'requires_php': header('Requires PHP')}
    for requirement in meta.values():
        if not re.fullmatch(r'\d+\.\d+(?:\.\d+)?', requirement):
            raise ValueError('Invalid requirement version')
    changelog = (ROOT / 'CHANGELOG.md').read_text(encoding='utf-8')
    match = re.search(r'^## ' + re.escape(version) + r'[^\n]*\n(.*?)(?=^## |\Z)', changelog, re.M | re.S)
    if not match:
        raise ValueError('Add a changelog entry before releasing')
    return f'<!-- herizon-cars-update {json.dumps(meta, separators=(",", ":"))} -->\n\n' + match[1].strip() + '\n\nInstall **herizon-cars-' + version + '.zip**. GitHub source archives are not installation packages.\n'


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--check', action='store_true')
    parser.add_argument('--notes', action='store_true')
    args = parser.parse_args()
    print(notes() if args.notes else 'Release version PASS: ' + validate_version())
