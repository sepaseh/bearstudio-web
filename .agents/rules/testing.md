# Testing

Choose the smallest test that can demonstrate the changed behavior. Use focused tests for pure rules and integration tests when HTTP, authorization, MariaDB, native image processing or file storage can affect the result. The [validation guide](validation.md) lists the executable checks and test environment.

## Placement and cases

- Put tests under `tests/` and extend the existing feature suite and synthetic fixture where practical. Keep test helpers out of the application projects. Name tests for a visible behavior or invariant and arrange setup, action and assertions so the result is easy to follow.
- Test meaningful success, rejection and failure cases. Verify stored and publicly visible state after a write. For authorization and concurrency changes, exercise rejected attempts, rollback and concurrent operations where they can affect the invariant. Distinguish dependency outages from invalid requests or invalid credentials.
- Follow the [privacy and fixture rule](safety-git.md#private-data-and-fixtures). Require temporary storage and disposable, explicitly test-owned MariaDB databases. Verify the current harness implements that isolation before running it; never point tests at operator databases. Preserve the local-target and ownership checks. Use deterministic input and controlled time when time is part of the contract.
- Wait for observable completion or cancellation. Dispose streams, readers, HTTP clients and processes before cleaning their owned resources. Record cleanup failures alongside the original failure. When testing in containers, track the resources created by the run and remove only those resources.
- Use HTTP tests for Blade responses and asset delivery. Follow [validation](validation.md#select-checks-by-change) for browser checks and [reporting](validation.md#report-results) for environment limits.

## Investigate an intermittent failure

Keep the first failure, the command, environment and synthetic conditions. Reproduce the affected scenario and use the owning test and source to distinguish a race, resource leak, dependency failure or product defect. A passing retry adds evidence and leaves the original failure part of the result. Keep required assertions and checks in place while investigating.
