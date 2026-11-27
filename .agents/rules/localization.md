# Language and culture

- Maintain English/LTR public Blade output, including document language, accessible names and SEO text. Follow the [public website](../../docs/design.md) and [SEO](../../docs/design.md) owners. Treat authored editorial content as data and preserve its provenance.
- API titles and validation messages belong to their owning backend operations. The independent management frontend owns its UI messages. Follow the [HTTP contract](api-contract.md) for stable wire behavior when display text changes.
- Preserve the [administrator mobile rules](../../docs/design.md) and the independent [public contact number rules](../../docs/design.md). Apply digit normalization only at the designated input boundary. Keep wire identifiers and dates governed by the contract when display formatting changes.
- A localization feature requires a confirmed design for language selection, fallback, direction and catalog ownership. Use a single owner for supported locales and complete translations with matching placeholders when adding catalogs. Keep language changes separate from stored editorial text.
- Verify affected display text, escaped content, `lang`/`dir`, metadata and keyboard labels. Use the [contract quality checks](contract-quality.md) for changes to HTTP messages and the [documentation language rule](documentation.md) for maintained guides.
