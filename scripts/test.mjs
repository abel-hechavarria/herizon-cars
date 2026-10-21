import { readdirSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';
const root = join(import.meta.dirname, '..');
function walk(dir) { return existsSync(dir) ? readdirSync(dir, { withFileTypes: true }).flatMap(e => e.isDirectory() ? walk(join(dir, e.name)) : [join(dir, e.name)]) : []; }
const files = walk(join(root, 'tests')).filter(f => f.endsWith('.php') && !f.endsWith('missing-dependencies.php'));
let failed = 0;
for (const file of files) {
  const r = spawnSync('php', [file], { encoding: 'utf8' });
  console.log(r.stdout.trim());
  if (r.status !== 0) { failed++; console.error(r.stderr || r.error); }
}
const js = [...walk(join(root, 'tests')), ...walk(join(root, 'src/js'))].filter(f => /\.test\.(m?js)$/.test(f));
const dependencyHelper = join(root, 'tests/php/missing-dependencies.php');
if (existsSync(dependencyHelper)) {
  for (const [flag, mode] of [['disable_classes=DOMDocument', 'dom'], ['disable_functions=gzdecode', 'gzip']]) {
    const r = spawnSync('php', ['-n', '-d', flag, dependencyHelper, mode], { encoding: 'utf8' });
    console.log(r.stdout.trim()); if (r.status !== 0) { failed++; console.error(r.stderr || r.error); }
  }
}
if (js.length) { const r = spawnSync(process.execPath, ['--test', ...js], { stdio: 'inherit' }); if (r.status !== 0) failed++; }
const releaseTests = spawnSync('python', [join(root, 'tests/release_test.py')], { stdio: 'inherit' });
if (releaseTests.status !== 0) failed++;
if (!files.length && !js.length) { console.error('No tests found'); failed++; }
process.exitCode = failed ? 1 : 0;
