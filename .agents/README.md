# Assistant guidance

Start with [project instructions](../AGENTS.md).

Use the [source map](../docs/source-navigation.md) to locate implementations and tests. The [read-only scenarios](evaluation/README.md) can check how an assistant discovers and applies the guidance.

This checkout owns the Laravel backend, public Blade website, database, authentication, authorization, media services and API consumed by the separate management panel. Keep management UI source and React tooling in the admin project. Use [design](../docs/design.md), [API contract](../docs/openapi.json) and [deployment](../DEPLOY.md) as the current application references.

## Installed skills

| Skill | Upstream source |
| --- | --- |
| [laravel-best-practices](skills/laravel-best-practices/SKILL.md) | [laravel/boost](https://github.com/laravel/boost/tree/c1c27ed55bf32e0c82f71663516f89e23eefde3e/.ai/laravel/skill/laravel-best-practices) |
| [testing-best-practices](skills/testing-best-practices/SKILL.md) | [laravel/boost](https://github.com/laravel/boost/tree/c1c27ed55bf32e0c82f71663516f89e23eefde3e/.ai/laravel/skill/testing-best-practices), rendered for PHPUnit |
| [web-design-guidelines](skills/web-design-guidelines/SKILL.md) | [vercel-labs/agent-skills](https://github.com/vercel-labs/agent-skills/tree/5a37a6329fbad3666c3207392a6a7c0110e92efb/skills/web-design-guidelines) |
| [playwright-cli](skills/playwright-cli/SKILL.md) | [microsoft/playwright-cli](https://github.com/microsoft/playwright-cli/tree/dc5f4d5616ab5a28ded12fd7101a4d49bd59a820/skills/playwright-cli) |
| [core-web-vitals](skills/core-web-vitals/SKILL.md) | [addyosmani/web-quality-skills](https://github.com/addyosmani/web-quality-skills/tree/afa8da942115f2961fdbfa80807ea0b232ff6c00/skills/core-web-vitals) |
| [performance](skills/performance/SKILL.md) | [addyosmani/web-quality-skills](https://github.com/addyosmani/web-quality-skills/tree/afa8da942115f2961fdbfa80807ea0b232ff6c00/skills/performance) |
| [seo](skills/seo/SKILL.md) | [addyosmani/web-quality-skills](https://github.com/addyosmani/web-quality-skills/tree/afa8da942115f2961fdbfa80807ea0b232ff6c00/skills/seo) |
| [accessibility](skills/accessibility/SKILL.md) | [addyosmani/web-quality-skills](https://github.com/addyosmani/web-quality-skills/tree/afa8da942115f2961fdbfa80807ea0b232ff6c00/skills/accessibility) |
| [modern-web-guidance](skills/modern-web-guidance/SKILL.md) | [GoogleChrome/modern-web-guidance](https://github.com/GoogleChrome/modern-web-guidance/tree/2b4aa13a72dd47cd135b06ce5abfccd92e19b24f/skills/modern-web-guidance) |
| [yeet](skills/yeet/SKILL.md) | [openai/skills](https://github.com/openai/skills/tree/49f948faa9258a0c61caceaf225e179651397431/skills/.curated/yeet) |
| [laravel-tdd](skills/laravel-tdd/SKILL.md) | [affaan-m/ECC](https://github.com/affaan-m/ECC/tree/4adeee6732a12d1540f49a4d50a38ce8c97f9a53/skills/laravel-tdd), optional test-first reference |
| [gh-fix-ci](skills/gh-fix-ci/SKILL.md) | [openai/skills](https://github.com/openai/skills/tree/49f948faa9258a0c61caceaf225e179651397431/skills/.curated/gh-fix-ci) |
| [frontend-design](skills/frontend-design/SKILL.md) | [anthropics/skills](https://github.com/anthropics/skills/tree/e4bb5d960d87126ff3b3a8af1a104b7db67560ba/skills/frontend-design) |
| [tailwindcss-development](skills/tailwindcss-development/SKILL.md) | [laravel/boost](https://github.com/laravel/boost/tree/c1c27ed55bf32e0c82f71663516f89e23eefde3e/.ai/tailwindcss/4/skill/tailwindcss-development) |
| [mariadb-features](skills/mariadb-features/SKILL.md) | [MariaDB/skills](https://github.com/MariaDB/skills/tree/5895ef8da1908d5b438d8ccb48653bcecedc090a/mariadb-features) |
| [mariadb-query-optimization](skills/mariadb-query-optimization/SKILL.md) | [MariaDB/skills](https://github.com/MariaDB/skills/tree/5895ef8da1908d5b438d8ccb48653bcecedc090a/mariadb-query-optimization) |
| [motion](skills/motion/SKILL.md) | [motiondivision/ai-kit](https://github.com/motiondivision/ai-kit/tree/1e424943582cbbdb6e3f410687e39f96451447bb/plugins/motion/skills/motion) |
| [github-actions-hardening](skills/github-actions-hardening/SKILL.md) | [github/awesome-copilot](https://github.com/github/awesome-copilot/tree/93fc8b9ee3ef56b9b5e7261e6ce92d314cb2c1cf/skills/github-actions-hardening) |
| [dependabot](skills/dependabot/SKILL.md) | [github/awesome-copilot](https://github.com/github/awesome-copilot/tree/93fc8b9ee3ef56b9b5e7261e6ce92d314cb2c1cf/skills/dependabot) |
| [validate-web](skills/validate-web/SKILL.md) | Project-specific testing and validation |
| [update-api-contract](skills/update-api-contract/SKILL.md) | Project-specific HTTP contract changes and review |
| [review-pr](skills/review-pr/SKILL.md) | Project-specific pull request and review thread workflow |
| [verify-release](skills/verify-release/SKILL.md) | Project-specific release, staging smoke and recovery verification |

Complete skill folders and upstream license evidence are retained. [Source revisions and entry-point hashes](skills-sources.json) identify the installed snapshots. Project rules and explicit user instructions take precedence over upstream guidance.

## Select a skill

Pick the workflow that owns the task. Add a second skill when it covers a distinct implementation or verification step. See the [coverage audit](skill-coverage.md) for the stack and selection decisions.

| Task | Primary guidance and when to add another skill |
| --- | --- |
| Laravel PHP, Blade integration, validation, application security or media lifecycle | `laravel-best-practices` with project rules and design. Use database or browser guidance when work reaches those boundaries. |
| SQL and schema | `mariadb-features` for server-specific capabilities and compatibility. Use `mariadb-query-optimization` for execution plans, indexing and measured query problems. |
| HTTP API or development Swagger | `update-api-contract` for the wire change and independent consumer compatibility. Use `validate-web` to select and run local checks. |
| PHP test design and review | `testing-best-practices` covers PHPUnit cases, assertions, isolation and suite diagnosis. Use `validate-web` for this project's fixture and execution conventions. |
| Local quality checks and CI verification | `validate-web` owns project commands, fixtures and evidence. Use `playwright-cli` for browser exploration or Playwright changes. |
| Public website implementation | `modern-web-guidance` for web platform choices. Add `frontend-design` for visual direction, `tailwindcss-development` for utility styling, or `motion` for animation with the existing vanilla Motion bundle. |
| UI review | `web-design-guidelines` for general UX, `accessibility` for a focused accessibility audit, and `seo` for metadata, indexing and discovery. |
| Website performance | `performance` for a broad diagnosis. Use `core-web-vitals` for a specific LCP, INP or CLS investigation and `motion` when the issue is in Motion animation code. Reuse measurements across skills. |
| GitHub Actions and dependencies | `github-actions-hardening` for workflow trust and permissions, `gh-fix-ci` for failing hosted checks, and `dependabot` for update policy. Use the project audit commands for local package checks. |
| PR preparation, review and feedback | Apply the [changed-file limit](rules/pull-requests.md#changed-file-limit) to the proposed diff. Use `review-pr` for hosted evidence, diff review and existing review threads. Follow the [PR rules](rules/pull-requests.md) for remote actions. |
| Release and recovery | `verify-release` owns release verification, staging smoke and isolated recovery. Follow the [deployment guide](../DEPLOY.md) for hosting procedures. |
| Combined commit, push and PR flow | Use `yeet` for this combined flow when explicitly requested. |

For Motion work, start with the bundled skill's [vanilla JavaScript guide](skills/motion/best-practices/motion.md) and verify APIs against the shipped bundle and [Motion's JavaScript documentation](https://motion.dev/docs/quick-start). The local install provides the offline guidance. The Motion MCP server, Motion+ and framework-specific integrations are outside this checkout's setup.

For GitHub skills, use the [workflow permissions](rules/safety-git.md#workflow-permissions) and [publication](rules/safety-git.md#authorization-and-publication) rules. `dependabot` applies to the existing Composer, npm and GitHub Actions policy. Use the existing local audit commands without adding the optional Copilot plugin or changing remote settings as part of skill use.

## Scope and use

- Preserve the existing Laravel and Query Builder patterns. Verify version-sensitive behavior against the installed framework or official documentation.
- Laravel and Tailwind guidance from Boost is installed. The Boost Composer package, MCP server and documentation tools are not configured by this skill installation.
- Use browser checks for public pages and integration flows; manage frontend panel tests in its own project.
- Measure performance and Core Web Vitals before optimizing; audit SEO and accessibility against rendered pages as well as source.
- Read [deployment instructions](../DEPLOY.md) before preparing hosting changes.
- Use yeet only when explicitly asked to stage, commit, push and open a PR as one flow. Installing it does not authorize those actions. Review scope and preserve unrelated work; follow project checks before committing.
- Skills may require external CLIs or services at execution time; their installation here does not install runtime tools or application dependencies.

## Testing and frontend work

Use [testing-best-practices](skills/testing-best-practices/SKILL.md) for Laravel test design and [validate-web](skills/validate-web/SKILL.md) with the [test guide](../docs/testing.md) for project fixtures and execution. [Laravel TDD](skills/laravel-tdd/SKILL.md) remains available for a specific test-first or mocking pattern when needed. The project's Query Builder, session, MariaDB and Playwright setup takes precedence over generic assumptions in upstream examples.

Use the Anthropic frontend-design skill for public Blade design work while preserving existing visual identity and patterns unless redesign is requested. Use the Laravel Tailwind v4 skill for utility styling; the current bundled public CSS is Tailwind 4.3.3. Installing its skill does not add a CSS build pipeline or shadcn. Without Boost search-docs, consult the installed version and official Tailwind documentation.

The standalone Laravel skills retain upstream Blade templates and include generated Markdown for their indexed links. The source manifest records the templates and the local render. The testing render uses PHPUnit 13, existing Composer commands and the project browser and isolation setup.

## Project rules

Read the relevant [architecture](rules/architecture.md), [API contract](rules/api-contract.md), [testing](rules/testing.md), [validation](rules/validation.md), [review](rules/workflow-review.md) and [Git safety](rules/safety-git.md) rules for the task. Rules define project constraints; skills provide task workflows.

## Database target

MariaDB is the current database target for application and integration testing, as confirmed by the project owner. SQLite is not an accepted substitute for database behavior verification. Application configuration, integration tests, browser tests and release checks target MariaDB. Use the isolated test server described in docs/testing.md. Never use operator databases as fixtures.

## MariaDB skills

- Use [mariadb-features](skills/mariadb-features/SKILL.md) when reviewing MariaDB-specific SQL, schema capabilities or database design choices. Use [mariadb-query-optimization](skills/mariadb-query-optimization/SKILL.md) for slow queries, execution plans, indexing, joins, ordering and pagination.
- Combine these with [Laravel best practices](skills/laravel-best-practices/SKILL.md) for Query Builder and framework integration, and [validate-web](skills/validate-web/SKILL.md) for isolated verification.
- The current CI baseline is MariaDB 10.11. Upstream skills assume 11.8 by default; the project baseline takes precedence. Verify the actual target version before recommending version-specific features. Do not replace current transaction, audit, pagination or API contracts merely because a skill suggests an alternative.
- Server configuration changes, restarts, production diagnostics and writes require task authorization. Do not enable Performance Schema or run executing ANALYZE statements against operator data as an automatic prerequisite for a source review; use existing evidence or the isolated test environment. Keep upstream skill files unchanged, with licenses and pinned sources.
