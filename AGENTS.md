# Project instructions

Read the [assistant guidance index](.agents/README.md). This project owns the Laravel public website and backend services for the independent management panel. Keep panel UI implementation in its own project. Follow current [design](docs/design.md), [API contract](docs/openapi.json) and [deployment](DEPLOY.md).

Preserve existing application patterns, unrelated changes, private configuration, database and uploaded media. Use synthetic test fixtures. Do not commit, push, publish or deploy without explicit authorization. Keep third-party skills intact, with their references and licenses.

## Select guidance by task

Use the [skill selection table](.agents/README.md#select-a-skill) to choose the primary workflow for the task. Add specialized guidance when the change crosses a separate boundary or needs further verification.
Use the [source map](docs/source-navigation.md) to find the owning implementation and tests.

- Preserve Laravel Query Builder, the shared write transaction, compatibility with the MariaDB 10.11 CI baseline and the independent admin host boundary. Follow [architecture](.agents/rules/architecture.md) and [data lifecycle](.agents/rules/data-lifecycle.md).
- Use [update-api-contract](.agents/skills/update-api-contract/SKILL.md) for HTTP contract changes, including changes to the development Swagger view. Keep `docs/openapi.json` as the source for API tools.
- Use [testing-best-practices](.agents/skills/testing-best-practices/SKILL.md) to design Laravel PHPUnit tests and [validate-web](.agents/skills/validate-web/SKILL.md) to select and run isolated project checks. Treat publishing and hosted evidence as separate from local verification.
- Use [review-pr](.agents/skills/review-pr/SKILL.md) for independent PR review and feedback. Use [verify-release](.agents/skills/verify-release/SKILL.md) for packaging, deployed-version checks and recovery drills.
- Open each new PR with at most 80 changed files. Reserve the remaining 20 up to the 100-file ceiling for fixes from that PR's review. Apply the [changed-file limit](.agents/rules/pull-requests.md#changed-file-limit) to the full diff against the verified merge base.
