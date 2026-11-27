# Code quality

- Solve the requested problem directly. Use abstractions and dependencies that serve a concrete use case. Verify that code, routes, exports and dependencies are unused before removing them within the task scope.
- Prefer clear names and straightforward control flow. Add comments for non-obvious reasoning, algorithms, constraints, or temporary workarounds rather than narrating the code.
- Treat blank lines as paragraph boundaries between responsibilities. Keep consecutive steps of one workflow together and separate guard clauses from the main path where this improves reading.
- Extract a focused helper when a function has several distinct responsibilities; do not create an abstraction only to shorten a small amount of clear repetition.
- Alphabetize keys, fields, or exports only when order has no behavioral or presentation meaning. Preserve route conventions, ordered data, dependency order, and formatter or linter decisions.
- Handle expected failure paths at the boundary that can explain or recover from them. Do not add defensive branches for impossible states simply to suppress an underlying defect.
- Apply the configured formatter to the intended files and preserve unrelated formatting and behavior. Add tooling only for a concrete project need. The [validation guide](validation.md) owns check commands.

## Types and boundaries

- Preserve PHP type declarations and nullable contracts. Narrow input with explicit checks and avoid casts that hide invalid external values.
- Validate or normalize external input at its owning boundary. Fail clearly for missing required configuration and handle optional values according to the [API contract](api-contract.md) where applicable.
- Use the owning type for shared values. Introduce separate types when a boundary gives the data different meaning. Use enums or other typed values for closed domains and cover every member when mapping their behavior.

## Static analysis

Read the reported SonarQube rule and confirm the risk in source. Prefer a small idiomatic fix and rerun the relevant checks when available. Explain evidence for suspected false positives and obtain authorization before changing provider classification or quality rules.
