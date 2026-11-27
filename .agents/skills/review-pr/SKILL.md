---
name: review-pr
description: Review a Bear Studio Web pull request for actionable defects or address its existing review feedback. Use for PR diffs, hosted checks, and review threads.
---

# Review a Bear Studio Web pull request

1. Identify the intended repository, PR, base, and current head. Read the proposed outcome and complete merge-base-to-head diff. Apply the [changed-file limit](../../rules/pull-requests.md#changed-file-limit) and use the review reserve only for fixes tied to that PR's feedback. Preserve the checkout and index during a review. When addressing existing feedback, read the original threads and the [feedback rules](../../rules/pull-requests.md#follow-up-on-coderabbit-feedback) before editing.
2. Follow [review criteria](../../rules/workflow-review.md#review-criteria). Read the owning source and tests behind the diff. In this project, check the public and admin host boundary, session and CSRF enforcement, permissions, transaction and cleanup invariants, and affected public pages when relevant. For HTTP changes, use [update-api-contract](../update-api-contract/SKILL.md) to check the OpenAPI document and the independent panel's expectations.
3. Match hosted checks and reviews to the verified head and base using the [hosted evidence rules](../../rules/pull-requests.md#read-hosted-evidence). For relevant local verification, use [validate-web](../validate-web/SKILL.md) and report it separately. A report against a previous head or a local test run cannot establish current hosted status.
4. Report actionable defects in priority order with a narrow source location, reproducible failure scenario, impact, and a practical correction. If addressing feedback, record the outcome for each item and link the fix only if its commit exists. Mention unavailable evidence and checks that could change the result.

Keep feedback as a local draft unless the user authorized posting to the existing thread or submitting a review. Follow [PR commenting](../../rules/pull-requests.md#pr-commenting) and [Git safety](../../rules/safety-git.md#authorization-and-publication) for remote actions.
