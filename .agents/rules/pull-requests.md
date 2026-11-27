# Pull requests

## Changed-file limit

Open each new pull request with at most 80 changed files, including drafts or replacements opened from an existing branch. Split a larger proposal before publication.

Reserve the remaining 20 files for fixes that address review feedback on that pull request. Keep unrelated changes in a separate PR. The total must stay at or below 100 changed files after every update. Split a change that would exceed that ceiling.

Count the complete diff between the proposed head and its merge base with the target branch. Include additions, edits, deletions, renames and binaries. Count a detected rename as one file. Recount when adding commits or retargeting the PR.

[Web Foundation CI](../../.github/workflows/foundation-ci.yml) and [Web CI](../../.github/workflows/ci.yml) fail the opening event above 80 files and fail every PR event above 100. Later events above 80 warn that the review reserve is in use. Check the complete local diff before opening a PR and verify that any use of the reserve matches actual review feedback.

## Prepare the change

- Keep one coherent outcome per PR. Split independently reviewable changes when helpful and identify prerequisites for dependent PRs.
- Describe the problem, approach, scope and [verification](validation.md#report-results). Add screenshots or a recording when they help review visible changes. Use closing references for fully resolved issues and ordinary links for contextual or partial work.
- Use the [commit subject convention](branches-and-commits.md#prepare-an-authorized-commit) for the PR title when squash merging. See the [creation workflow](workflow-review.md) for preparation and publication.

## PR commenting

Do not post standalone comments in the PR conversation. Post feedback replies only in the existing review threads when authorized. Keep responses as local drafts when a matching thread or posting authorization is unavailable.

## Follow up on CodeRabbit feedback

After each round of addressing CodeRabbit feedback and committing any resulting changes, write a concise, separate response for every item from that round. Follow the [PR commenting rule](#pr-commenting). Identify each item in a local draft by its review link or source location.

- For a change, state what was done and where. Link the actual fix commit or describe a relevant check when useful.
- For an item left unchanged, partially addressed or deferred, state the decision, the reason and any remaining work. Keep duplicate items traceable to the primary response.
- Check the responses against the round's feedback before handing them off. Follow [safety and Git](safety-git.md#authorization-and-publication) for resolving threads.

## Read hosted evidence

- Match each check, review and approval to the current PR head and applicable base. Recheck after a new commit, analysis or base change. Verify the provider's result and project binding, including whether the scan or review ran. Use [validation reporting](validation.md#report-results) for results and their limits.
- Before requesting a configured external review, check its actual prerequisites, available results, outstanding requests and rate limits. Reuse a substantive review for the same head where it still applies. Follow [static-analysis guidance](code-quality.md#static-analysis) for findings and verify the provider's status before reporting one as resolved.
- Verify required checks and other protections for the target branch in the current repository settings. Keep required checks, approvals and conversations intact. Follow [safety and Git](safety-git.md#authorization-and-publication) for changes to protection settings.
- See the [CI workflow](../../.github/workflows/ci.yml), [workflow safety](safety-git.md#workflow-permissions) and [Sonar setup](validation.md#github-actions) when changing or diagnosing hosted checks.
