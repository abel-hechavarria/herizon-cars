"""Deterministic runtime-only archive; run from any checkout, without WordPress."""
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
import hashlib
import json
from release import ROOT, validate_version


def package():
    version = validate_version()
    output = ROOT / 'build'
    output.mkdir(exist_ok=True)
    destination = output / f'herizon-cars-{version}.zip'
    names = ['style.css', 'functions.php', 'readme.txt', 'LICENSE', 'THIRD-PARTY-NOTICES.txt',
             'inc', 'blocks', 'assets', 'template-parts', 'dist', 'languages']
    files = []
    for name in names:
        source = ROOT / name
        if not source.exists():
            raise ValueError(f'Missing runtime path: {name}')
        files.extend([source] if source.is_file() else sorted(p for p in source.rglob('*') if p.is_file()))
    manifest_path = ROOT / 'dist/.vite/manifest.json'
    if not manifest_path.is_file():
        raise ValueError('Build before packaging: missing dist/.vite/manifest.json')
    manifest = json.loads(manifest_path.read_text(encoding='utf-8'))
    if not isinstance(manifest, dict) or not manifest:
        raise ValueError('Invalid build manifest')
    for entry in manifest.values():
        for name in [entry['file'], *entry.get('css', [])]:
            target = (ROOT / 'dist' / name).resolve()
            if not target.is_relative_to((ROOT / 'dist').resolve()) or not target.is_file():
                raise ValueError('Missing or invalid compiled asset: ' + name)
    with ZipFile(destination, 'w', ZIP_DEFLATED, compresslevel=9) as archive:
        for file in sorted(files, key=lambda path: path.relative_to(ROOT).as_posix()):
            if file.is_symlink():
                raise ValueError('Symlinks cannot be packaged')
            info = ZipInfo('herizon-cars/' + file.relative_to(ROOT).as_posix(), (2026, 1, 1, 0, 0, 0))
            info.compress_type = ZIP_DEFLATED
            info.create_system = 3  # Stable ZIP metadata on Windows and Linux.
            info.external_attr = 0o100644 << 16
            archive.writestr(info, file.read_bytes(), compresslevel=9)
    digest = hashlib.sha256(destination.read_bytes()).hexdigest()
    (output / 'SHA256SUMS').write_text(f'{digest}  {destination.name}\n', encoding='utf-8')
    print(destination)
    print(f'SHA256 {digest}')
    return destination


if __name__ == '__main__':
    package()
