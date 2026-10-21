import { execFileSync } from 'node:child_process';
import { join } from 'node:path';
execFileSync('python', [join(import.meta.dirname, 'package.py')], { stdio: 'inherit' });
