# Validation

## Current commands

The [test guide](../../docs/testing.md#commands) owns commands, prerequisites and evidence limits. Use [validate-web](../skills/validate-web/SKILL.md) for the Laravel workflow. Run commands from the repository root.

## Select checks by change

- For documentation-only changes, check links and source consistency and run npm run check:guidance.
- For application changes, select focused tests first, then the applicable quality, PHPUnit, contract, browser and release checks in the test guide.
- For rendered changes, inspect layout, keyboard behavior and interactions at representative viewports. Preserve the independent panel boundary.
- For HTTP changes, use [contract quality](contract-quality.md). Schema review, real-response tests and browser checks provide separate evidence.
- Repeat or broaden passing checks when new changes, failures or unresolved concerns justify it.

## GitHub Actions

Read the actual [CI workflow](../../.github/workflows/ci.yml) and [CI limits](../../docs/testing.md#ci-and-limits). Match hosted results to the intended revision. Local checks do not establish hosted success, branch protection or DirectAdmin deployment. Do not assume a scanner integration exists without configuration and execution evidence.

## Report results

- State checks, change set, observed results and material limitations. Identify pending, failed, skipped, stale or unavailable evidence.
- Distinguish local tests, browser checks, hosted CI, scanner execution, human review and deployment.
- Tie coverage to the tested revision and measured code. Keep dated reports out of maintained guides.
