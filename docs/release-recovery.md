# Verify a release and recover Bear Studio Web

Use the [deployment instructions](../DEPLOY.md) and the [DirectAdmin layout](../deploy/full-site/INSTALL.md) for installation. Recover this backend, its MariaDB database and its private media together. The admin React build has its own release identity and procedure.

## Prepare and verify the package

1. Run the [project checks](testing.md) and package the backend with `php scripts/package-release.php`. That command runs the local smoke test against the exported code and a disposable MariaDB database.
2. Record the value in the exported `bearstudio-backend/release-id`. Each new export gets an opaque package identifier. Keep this file private alongside the exact exported backend and activate the complete package and both PHP entry points together. Keep the installed `.env` and storage with the appropriate environment. A failed packaging command can leave an incomplete export under `.build`.
3. Use the candidate version on private staging. If the host uses a response cache, bypass or invalidate old dynamic responses before verification. Refresh PHP workers if the host retains old code in its opcode cache. The identifier is a deployment check and does not attest to the integrity of every file or the freshness of every worker.

## Check the deployed hosts

Run this command from the project checkout with Node.js 24. Substitute the two intended environment origins and the release ID copied from the candidate package. Use `off` for private staging with indexing disabled and `on` where indexing is enabled.

```text
node scripts/check-deployed-release.mjs https://staging.example.test https://admin-staging.example.test <release-id> off
```

The checker uses credential-free HTTPS GET requests and rejects redirects. It checks public pages and discovery responses, confirms the public host returns a JSON 404 for the admin session endpoint, and confirms the admin host returns a JSON 401 without signing in. Every checked response must identify the same expected backend release. The panel build, authenticated behavior, and actual data require separate evidence. Check those with a synthetic operator and media on private staging.

## Capture a matched recovery point

1. Schedule a write pause for the panel, API and any maintenance workers that can change MariaDB or media. Let in-flight writes finish and prevent schema changes until the snapshot is complete.
2. Record the backend package ID, migration level and timestamp. Capture a logical dump of the one application database and a copy of the complete private media tree under `storage/app/private/media`, including files with pending cleanup. Record a reference to the matching protected application key and configuration through the host's secret store.
3. Keep the dump and media outside the document roots and away from release packages. Record checksums for both parts, store them together with restricted access and verify completion before resuming writes. Use the installed compatible [MariaDB dump tool](https://mariadb.com/docs/server/clients-and-utilities/backup-restore-and-import-clients/mariadb-dump). An InnoDB database snapshot alone cannot synchronize the media copy.

## Restore or roll back

1. Restore the verified pair into a fresh, isolated MariaDB database and private storage path using the matching PHP release and configuration. Keep the original snapshot and the outgoing system state recoverable. Use the [MariaDB restore guide](https://mariadb.com/docs/server/mariadb-quickstart-guides/mariadb-restore-guide) for imports.
2. Check the migration history, representative records and media references, and the expected pending cleanup entries. Verify public rendering, delivered media bytes, login and permission boundaries in isolation. Decide how to revoke restored sessions before reactivation when appropriate.
3. A code-only rollback requires a previous release that can use the current schema and stored media. If state must be rolled back too, account for writes after the recovery point before switching the database and media together under a write pause. Verify both hosts again against the chosen release ID before reopening traffic.

## Run the isolated recovery drill

The drill uses the [local MariaDB test settings](testing.md#isolation-and-fixtures) and installed PHP development dependencies. Put compatible `mariadb-dump` and `mariadb` clients on `PATH`, or set `BEARSTUDIO_MARIADB_DUMP` and `BEARSTUDIO_MARIADB_CLIENT` to their respective executables. Run from the project root.

```text
php vendor/bin/phpunit tests/Recovery/RecoveryDrillTest.php
```

The test uploads a generated image through the application, dumps the temporary fixture database, copies its private media and restores both into fresh locations. It verifies migration history, database metadata, image and thumbnail hashes, the public media response, continuity of the synthetic application key and permission boundaries after signing in again. The fixture uses two disposable schemas and a temporary storage tree. It takes no backup input paths and never imports operator data. A passing drill verifies the synthetic round trip against the local MariaDB baseline.
