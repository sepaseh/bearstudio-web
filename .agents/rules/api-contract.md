# HTTP API contract

Use the [OpenAPI document](../../docs/openapi.json) and owning feature design for wire behavior. See [API usage](../../docs/design.md) for the runnable Development contract.

- Update the handler, DTOs, affected OpenAPI operations and behavior tests together when a request or response changes. Cover affected validation, security, status codes and headers.
- Follow the published JSON and query names, types and casing. Keep optional, omitted and explicit-null values distinct where the operation defines different meanings. Verify which defaults belong to the server before adding a field to a client example. Preserve documented PUT replacement and PATCH semantics.
- Document bodyless methods with the response and headers they actually send. Preserve protocol names, content types, pagination and meaningful order in arrays and parameter lists. See the [content contract](../../docs/design.md) for ordering and reference rules.
- Follow the [error contract](../../docs/design.md). Preserve HTTP status, `X-Error-Code`, `X-Request-Id` and the JSON `message` with optional field-validation `cause`. Keep raw exception details out of responses and examples.
- Keep cookie, antiforgery, permission and public-read behavior aligned with [authentication](../../docs/design.md) and the owning feature. Apply the [data lifecycle rule](data-lifecycle.md) to status, deletion, retention and reference behavior.
- Review compatibility and rollout for independently released consumers, including `bearstudio-admin`, when any wire behavior changes. Confirm assumptions about a consumer before relying on it. A backend contract change alone grants no authority to edit or release another repository.

Use [contract quality](contract-quality.md) to verify the resulting HTTP behavior.
