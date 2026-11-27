# Documentation

- Maintained documentation and assistant instructions for this version must be English. The user's conversation language and future product strings are separate concerns.
- Organize material around the reader's task. Use tutorials for learning, procedures for completing an operation, references for contracts and explanations for design decisions. Procedures state prerequisites, working directory when relevant, actions, expected results and relevant recovery.
- Keep one owner for each rule, command or contract. Link to that owner from other documents.
- Update the owning document alongside an agreed change. Verify implementation claims against the owning source, contracts and current configuration. Distinguish proposals, confirmed decisions, implemented behavior and historical material. Optional hooks and automation require an actual integration.
- Use repository-relative paths for project files and document-relative links for navigation. Verify changed paths and heading anchors, and compare changed commands with their scripts or workflows. Inspect other checkouts or historical evidence when the task requires it. Keep fixed historical Git refs, machine-specific reference paths and saved artifacts out of instruction inputs.
- Use [validation reporting](validation.md#report-results) for executed commands and dated results. Host instruction discovery and execution require their own evidence.
- Follow the [privacy rule](safety-git.md#private-data-and-fixtures) for examples and fixtures. Keep the documentation language requirement separate from decisions to translate or import a reference product's UI or catalog.
- Keep guide names stable and task-oriented. Register only existing rules, procedures and checks.
- Use the root README to explain what the application does, how to run it and where to find the owning guides. Use one descriptive page title, a consistent heading hierarchy, descriptive relative links and a language tag on command or code fences. Cite the external source when its contract or procedure is required.

For writing conventions, see [GitHub's README guidance](https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-readmes), [Diátaxis](https://diataxis.fr/), [Google's documentation headings](https://developers.google.com/style/headings) and [Microsoft's procedure guidance](https://learn.microsoft.com/en-us/style-guide/procedures-instructions/writing-step-by-step-instructions).
