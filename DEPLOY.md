# Deploy Bear Studio on Linux

Use PHP 8.4.1+ in the 8.4 series with PDO MySQL, GD with WebP, DOM, Fileinfo, Intl, Mbstring and Laravel required extensions. Run the [verification commands](docs/testing.md) before preparing a release.

The website uses `https://3earstudio.com`; the independent management application uses `https://admin.3earstudio.com`. Both use the same private Laravel backend. Deploy the panel UI from its own project and route only `/api/v1/` on its origin to Laravel.

Follow [DirectAdmin installation](deploy/full-site/INSTALL.md) for layout and routing. `php scripts/package-release.php` exports source and locked production dependencies beneath `.build/`; it does not read or export real environment files, MariaDB databases or media.

Preserve the installed `APP_KEY`, database and storage on upgrades. Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `APP_URL=https://3earstudio.com` and `ADMIN_HOST=admin.3earstudio.com` in the private server environment. Disable staging indexing with `APP_ALLOW_INDEXING=false`. Apply migrations before reopening traffic. Use a private MariaDB database and least-privilege application credentials. Back up the database before schema upgrades.

Use the [release and recovery runbook](docs/release-recovery.md) for the matched database and media backup, isolated recovery drill, rollback and post-deploy HTTP check. Test login, permission boundaries and a synthetic upload on private staging. Local CI does not establish that the real server is configured correctly.
