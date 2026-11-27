# Branches and commits

## Working checkout

- Follow [Git safety](safety-git.md#working-state-and-destructive-actions) before changing branches. Check available worktrees and reuse a suitable checkout. Use an isolated worktree or export when the working state affects the proposed change or its verification.
- Verify the intended base from the request, current repository or PR metadata. For an unborn branch, identify a committed base only when the task needs one.
- Choose a short purpose-based branch name. Use `codex/<short-kebab-purpose>` by default and include a verified issue number when helpful. A name chosen by the user takes precedence. Keep priority, labels and assignees in issue or PR metadata.

## Prepare an authorized commit

Use the [commit workflow](workflow-review.md) to prepare and verify the change. Write a Conventional Commit subject up to 72 characters. Use a lowercase kebab-case scope when it adds context, an imperative description and a `!` marker for a breaking change. Common types include `build`, `chore`, `ci`, `docs`, `feat`, `fix`, `perf`, `refactor`, `revert` and `test`.

```text
<type>[(scope)][!]: <description>
```

Use a body when the reason, tradeoff or verified issue link needs explanation. Add authorship trailers only when requested or otherwise established by the author.
