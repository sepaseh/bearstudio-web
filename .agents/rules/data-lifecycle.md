# Data lifecycle

Read the owning feature's service, transaction, MariaDB foreign keys, [HTTP contract](api-contract.md) and tests for deletion, status, assignment or retention work.

## Deletion and references

- Apply [content rules](../../docs/design.md) to editorial records and [access rules](../../docs/design.md) to accounts and roles. An allowed delete removes the owned database row. Deactivate user accounts through the access operations.
- Verify actual dependencies before removing a record. Current references protect categories, images and videos. A role assignment still counts when the member is inactive.
- Reject verified in-use conflicts with the owning operation's controlled response. Leave records, references and assignments unchanged on rejection. Clear or replace references through authorized feature operations before retrying. Apply cascades only to owned rows allowed by the documented relationship.

## Transactions and verification

- Check current authorization, record state and references within the owning transaction. Preserve restrictive relationships and the concurrency guarantees that protect against races. Map known dependency failures to a conflict and keep audit atomic with the write. Use the [API contract](api-contract.md) for error responses.
- Preserve the existing logical deletion and durable file cleanup design for managed [images](../../docs/design.md) and [videos](../../docs/design.md). SQL metadata, pending cleanup and private file bytes have separate lifetimes. Preserve ownership and retry rules when cleanup can fail or overlap with a write.
- Verify eligible deletion, in-use conflict without partial changes and concurrent reference changes where applicable. Use the [testing rule](testing.md) for fixtures and the [migration guide](../../docs/design.md) for schema changes.
