# Evaluate project guidance discovery

Run each prompt in a fresh project chat without providing source paths or the expected evidence. Keep these exercises read-only. Use only the repository and synthetic examples. Record the skill selected, paths inspected and missing evidence. A passing link check does not establish that a tool discovered and applied the skill by itself.

| Prompt | Expected evidence |
| --- | --- |
| Where should I start if a panel API response includes a new field? | Select `update-api-contract`. Identify [routes](../../routes/web.php), the [OpenAPI document](../../docs/openapi.json) and [real response tests](../../tests/Contract/OpenApiTest.php). Mention the independent panel as a consumer. |
| How would you verify deleting an image referenced by a project? | Identify [media lifecycle](../../app/Support/MediaLifecycle.php), the [lifecycle rule](../rules/data-lifecycle.md) and [synthetic media tests](../../tests/Feature/MediaTest.php). Explain the expected rejection and unchanged stored files and records. |
| What shows that staging is running the candidate backend and keeping host boundaries? | Select `verify-release`. Distinguish [local package verification](../../scripts/verify-release.php) from the [deployed check](../../docs/release-recovery.md#check-the-deployed-hosts). Identify the expected release marker and both host checks. |
