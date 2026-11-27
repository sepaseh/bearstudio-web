# Backend architecture

Use [design](../../docs/design.md), [API contract](api-contract.md) and [deployment](../../DEPLOY.md) as current references.

- This checkout owns the Laravel public Blade website and API. Keep management React UI and tooling in the independent admin project.
- Preserve the existing controllers, middleware, support classes and Query Builder patterns. Follow Composer mappings and existing container registration; introduce abstractions only for concrete needs.
- Put complex feature writes in focused actions when they outgrow the HTTP handler. Keep request parsing and status/header mapping at the HTTP boundary. Keep the permission recheck, business invariants, committed changes and any audit in the same shared write transaction.
- Preserve the configured public/admin host boundary, host-only session cookie and explicit CSRF validation.
- Keep MariaDB, sessions and media outside public storage. Preserve transaction and locking guarantees, staged uploads, committed metadata and cleanup ownership described in design.
- Check authorization and limits before reading uploads. Use [data lifecycle](data-lifecycle.md) and [source dependencies](imports.md) for affected boundaries.
- Verify version-sensitive behavior against installed dependencies or official documentation.
