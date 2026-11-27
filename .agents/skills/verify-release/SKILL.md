---
name: verify-release
description: Verify a Bear Studio Web backend release package, check both deployed hosts against its release ID, or rehearse MariaDB and private media recovery when investigating deployment or rollback readiness.
---

# Verify a Bear Studio Web release

Start with the [release and recovery runbook](../../../docs/release-recovery.md). Choose local package verification, hosted HTTP checks or a synthetic recovery drill to match the requested evidence.

- For packaging, use the project package command and report its smoke result and the candidate release ID. Derive expected deployed identity from the candidate package rather than whichever release the host reports.
- For a deployed check, use the intended public and admin HTTPS origins, expected package ID and indexing mode. Use the credential-free checker. If a host returns an unexpected identifier or status, report the failing host and path and check release placement, PHP workers and caches before claiming success.
- For recovery, verify MariaDB and private media as one recovery point. Rehearse with the local test database and generated media. Use the runbook to assess the effects of migrations, intervening writes and session restoration before an authorized rollback.
- Separate local package and synthetic recovery results from hosted verification. Check the independent panel's build and authenticated flows in their owning workflows. Deployment, migration and restoration of real state follow the project's authorization rules.
