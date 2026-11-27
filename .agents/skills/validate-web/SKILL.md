---
name: validate-web
description: Write and run Bear Studio Web backend tests, contract checks, browser smoke tests and CI validation. Use for Laravel verification and test infrastructure in this checkout.
---

# Validate Bear Studio Web

Read [testing](../../../docs/testing.md) for commands and evidence limits. Read the affected controller or support class, migrations and [OpenAPI contract](../../../docs/openapi.json) before selecting cases.

- Use PHP 8.4 and the locked development dependencies. Keep PHPUnit feature tests in this checkout; panel React component and interface tests belong to the independent admin checkout.
- This application authenticates through `AdministratorSession`, a database ticket, a Laravel cookie and an explicit CSRF header. Exercise that flow; `actingAs()` and Sanctum tokens do not represent it.
- Database writes use Query Builder. Use synthetic fixture builders matching the actual schema rather than adding Eloquent models for tests.
- Isolate the environment before Laravel boots. Never load operator `.env`, application keys, databases or uploaded files. Use disposable, explicitly test-owned MariaDB databases and temporary storage. Verify transaction, locking, foreign-key and migration behavior against MariaDB; SQLite results do not establish MariaDB correctness. Verify the harness supports this isolation before running database tests.
- Cover successful behavior, invalid input and permission boundaries. For rejected writes, check that database rows, audit records and media remain consistent. Test session revocation and stale catalog updates.
- Use [update-api-contract](../update-api-contract/SKILL.md) for API changes. Keep route parity, real-response schema checks and host isolation in the test matrix.
- Run focused tests first, then the documented quality, test, contract and browser commands. Preserve failures; fix source defects only when demonstrated by a test. Do not lower assertions, static-analysis levels or coverage requirements to make a failing run green.
- CI configuration is local source until executed by the remote provider. Installing skills or writing a workflow does not establish a hosted CI pass, authorize publication or configure branch protection.
- Use [verify-release](../verify-release/SKILL.md) for packaged and deployed release evidence and the opt-in synthetic recovery drill.
