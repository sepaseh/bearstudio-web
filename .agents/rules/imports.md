# Source dependencies

- Follow the existing Laravel layout and Composer PSR-4 mappings. Inspect controllers, middleware, support classes and service registration before moving responsibilities.
- Check PHP imports, fully qualified names, Composer dependencies, container bindings, routes and Blade references when moving code. Avoid cycles.
- Keep test fixtures, other checkouts, private configuration and generated runtime inputs off application dependency paths. Panel UI dependencies belong in the independent admin project.
- Run applicable [validation checks](validation.md). Passing static analysis does not establish that a dependency belongs at a particular boundary.
