# Test and validate Bear Studio Web

Use PHP 8.4.1 or later in the 8.4 series, Composer 2 and Node.js 24. Install locked development dependencies with `composer install` and `npm ci --ignore-scripts`. Install the browser with `npx playwright install chromium` (add `--with-deps` on Linux). Production installations use `composer install --no-dev`.

## Commands

| Command | Evidence |
| --- | --- |
| `composer quality` | Composer metadata, Laravel Pint formatting and Larastan/PHPStan level 5 |
| `composer test:unit` | Pure input, password and HTML safety cases |
| `composer test:feature` | Real Laravel HTTP requests, authentication, access, media, public pages and migrations |
| `composer test:contract` | Host, method and route parity plus sampled real responses validated against OpenAPI |
| `composer test` | All PHPUnit suites |
| `composer test:coverage` | Clover XML and HTML under ignored `coverage/`; requires Xdebug coverage mode or PCOV |
| `composer audit` and `npm audit` | Known dependency advisories |
| `npm run check:guidance` | Maintained guide links, skill inventory and upstream entry-point hashes |
| `npm run typecheck` | TypeScript validation of browser tests and Playwright configuration |
| `npm run test:browser` | Chromium desktop/mobile checks against a disposable local Laravel server |
| `node --test scripts/check-deployed-release.test.mjs` | Response checks and input validation for the hosted release checker |
| `php scripts/package-release.php` | Isolated production dependency installation and release layout |
| `php vendor/bin/phpunit tests/Recovery/RecoveryDrillTest.php` | Opt-in database and private media restoration using native MariaDB clients. See the [recovery drill](release-recovery.md#run-the-isolated-recovery-drill). |

Run a focused test while editing, for example `composer test -- --filter AuthenticationTest`. Use `composer format` to apply the configured PHP formatting rules. Before handing off a complete change, run quality, tests, dependency audits, guidance, browser and release checks.

## Isolation and fixtures

PHPUnit sets synthetic environment values in [bootstrap](../tests/bootstrap.php) before loading Laravel. The [test application](../tests/TestCase.php) changes its environment, storage and bootstrap/cache directories before booting, so it does not load the operator `.env` or write application logs, views, sessions, MariaDB or media. Test storage is randomly named under the OS temporary directory and removed afterward.

Database cases create a randomly named disposable MariaDB database per application instance and drop it afterward. Use a dedicated local test server and BEARSTUDIO_TEST_DB_HOST, BEARSTUDIO_TEST_DB_PORT, BEARSTUDIO_TEST_DB_USER and BEARSTUDIO_TEST_DB_PASSWORD to configure it. The test account needs CREATE/DROP DATABASE within this dedicated test environment. Tests reject nonlocal hosts and never use application DB credentials. Migration tests cover foreign keys, rollback, repeat migration and preserved legacy records.

Browser tests start their own PHP process with a disposable MariaDB database and temporary storage. Existing servers are not reused. To select a PHP executable outside PATH, set `PHP_BINARY` in the invoking terminal. Set PLAYWRIGHT_CHANNEL=msedge to use an installed Edge browser when the bundled Chromium download is unavailable. The tests check the public site and backend host boundary; management panel components and interface tests remain in the independent panel project.

## CI and limits

[Web Foundation CI](../.github/workflows/foundation-ci.yml) runs from the first publication stage and adds Composer, PHP syntax and OpenAPI checks when the Laravel sources are present. [Web CI](../.github/workflows/ci.yml) runs quality and audits, PHPUnit coverage, browser checks and production packaging as separate Linux jobs. Both workflows apply the [changed-file rule](../.agents/rules/pull-requests.md#changed-file-limit) on pull requests, with 80 files at opening and a total ceiling of 100 after review fixes. The final `check` job requires every applicable job to succeed. Configure that check as required in the actual hosting repository's branch protection when publishing. This checkout does not configure remote protection or deploy anything.

Coverage reports show executed application lines; a passing suite is not evidence that all behavior is covered. The coverage command enforces an initial 70% application statement floor from the Clover report. The suite prioritizes security boundaries and complete workflows. Do not infer full coverage from test counts or a green pipeline. Use the generated HTML report to select further cases. Browser smoke checks do not replace manual assistive-technology review, field performance data or testing the actual DirectAdmin server.

The release exporter includes backend source, public assets and locked production dependencies. It excludes real environment files, databases, uploads, development packages, tests and panel implementation. It creates API integration files for the separate admin origin; build and deploy the React application from the panel project.
