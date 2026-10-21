import { readdirSync } from 'node:fs';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';
const root = join(import.meta.dirname, '..');
function walk(dir) {
  return readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
    if (['node_modules', 'dist', '.git'].includes(entry.name)) return [];
    return entry.isDirectory() ? walk(join(dir, entry.name)) : [join(dir, entry.name)];
  });
}
let failures = 0;
for (const file of walk(root)) {
  const args = file.endsWith('.php') ? ['php', ['-l', file]] : /\.(m?js)$/.test(file) ? [process.execPath, ['--check', file]] : null;
  if (!args) continue;
  const result = spawnSync(args[0], args[1], { encoding: 'utf8' });
  if (result.status !== 0) { failures++; console.error(result.stderr || result.stdout || result.error); }
}
console.log(failures ? `Lint failures: ${failures}` : 'PHP and JavaScript syntax PASS');
process.exitCode = failures ? 1 : 0;
