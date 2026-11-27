# Workflow and review

## Implementation and handoff

- Read the project context, relevant contracts and actual source. Identify the intended outcome, acceptance criteria and affected responsibilities. Follow [code quality](code-quality.md) for implementation and [documentation](documentation.md) for updates to the owning guide.
- Run independent work concurrently when appropriate. Keep dependent operations and mutations in their required order.
- Inspect the final diff and use the [validation workflow](../skills/validate-web/SKILL.md). At handoff, explain what changed and [report the verification and its limits](validation.md#report-results).

## Review criteria

For a pull request or its review threads, use the [review-pr workflow](../skills/review-pr/SKILL.md).

- Check changed behavior, edge cases, lifecycle and cleanup, errors, permissions, sensitive data, accessibility, responsive behavior and content language when relevant. For HTTP changes, use the [contract quality checks](contract-quality.md).
- Use the existing tools for style checks. Separate questions and optional suggestions from actionable findings. Report if no actionable findings remain and identify any material gaps in verification.

| Priority | Typical impact |
| --- | --- |
| P0 | Immediate security exposure, irreversible data loss or a widespread outage |
| P1 | Serious regression, broken authorization or a failed critical workflow |
| P2 | Localized correctness, reliability, accessibility, performance or maintainability defect |
| P3 | Low-risk improvement with a concrete benefit |
