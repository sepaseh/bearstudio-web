import { spawn, spawnSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';

const scratch = mkdtempSync(path.join(tmpdir(), 'bearstudio-browser-'));
const php = process.env.PHP_BINARY || 'php';
const env = { ...process.env, BEARSTUDIO_TEST_STORAGE: scratch };
for (const directory of ['framework/views', 'framework/sessions', 'framework/cache', 'logs', 'db', 'bootstrap/cache']) {
    mkdirSync(path.join(scratch, directory), { recursive: true });
}
const setup = spawnSync(php, ['tests/browser/bootstrap.php'], { env, stdio: 'inherit', windowsHide: true });
if (setup.status !== 0) {
    spawnSync(php, ['tests/browser/bootstrap.php', 'cleanup'], { env, stdio: 'inherit', windowsHide: true });
    rmSync(scratch, { recursive: true, force: true });
    process.exit(setup.status || 1);
}
const server = spawn(php, ['-S', '127.0.0.1:8765', '-t', 'public', 'tests/browser/router.php'], { env, stdio: 'inherit', windowsHide: true });
for (const signal of ['SIGINT', 'SIGTERM']) process.on(signal, () => server.kill());
server.on('exit', code => {
    spawnSync(php, ['tests/browser/bootstrap.php', 'cleanup'], { env, stdio: 'inherit', windowsHide: true });
    rmSync(scratch, { recursive: true, force: true });
    process.exit(code || 0);
});
