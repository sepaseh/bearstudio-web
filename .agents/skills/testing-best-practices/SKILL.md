---
name: testing-best-practices
description: Design or review PHPUnit tests for Laravel. Use when selecting cases, assertions, fixtures, isolation, HTTP or security coverage, or diagnosing a slow test suite.
license: MIT
metadata:
  author: laravel
---
# Testing best practices

This skill provides rules for designing Laravel tests. Verify Laravel syntax against the installed framework or its official documentation. Use the [PHPUnit documentation](https://phpunit.de/documentation.html) for the locked PHPUnit version.

## Bear Studio Web profile

- Laravel Boost's [upstream templates](SKILL.blade.php) are retained alongside these Markdown files. The local render selects the PHPUnit 13 profile. The [project test guide](../../../docs/testing.md) and [validate-web](../validate-web/SKILL.md) own execution, fixture isolation and evidence.
- Use the existing test harness and its Query Builder fixtures, disposable MariaDB databases, and real cookie and CSRF flow. Treat Eloquent factories, framework guards and policy examples as patterns to adapt to the actual project boundaries. The project test configuration takes precedence over generic examples or tooling recommendations.

## Consistency first

Read nearby tests before you choose syntax and organization.

A pattern repeated throughout the project is a convention, and project conventions take precedence over this skill. Follow them and give new tests the same structure.

These rules govern the tests you write now. An existing test that follows a project convention is not defective merely because it conflicts with this skill. Do not delete or rewrite it. If the convention has drawbacks, explain them and let the user decide.

## What to test

Read this section before you write a test.

- Test observable behavior and application contracts. A test must pass after an implementation change if the behavior stays the same.
- Cover every changed decision and each applicable high-value failure mode. A decision is a branch, a validation, a calculation, or an authorization.
- Exercise declarations through behavior instead of repeating their text.
- Leave framework behavior to framework tests. Testing project configuration is not testing the framework. A constrained relationship, cast, scope, or validation rule belongs to this project.
- Keep every test that can detect a distinct defect. When two tests detect the same defect, trim the higher-layer test to one case and report the duplication. Do not delete an existing test.
- Write a feature test first. Write a unit test only for logic that does not use the framework.
- Cover HTTP behavior at the feature boundary. Use the existing Playwright suite for behavior that requires a real browser.
- Use the test tools that the project installs. Add a new test dependency, plugin, or browser only after the user asks for it.

## How to apply

1. Read the code under test. Read the tests in the same directory. Identify every decision in the code.
2. Select every applicable branch in the rule index. Read every selected rule file.
3. Report each defect in the code before you write a test. Examples are a method with no body, a policy that no action calls, and a write action with no validation. Test the actual behavior. Report the defect to the user.
4. Write the tests. Run the smallest set of tests that covers the change. The tests must pass.
5. Check every applicable item in `rules/review.md` and every selected rule file. Resolve every mismatch before completion.

## Rule index

Most changes need more than one rule file.

| Subject | Rule file |
| --- | --- |
| Test framework features that may already do the work | [`rules/finding-features.md`](rules/finding-features.md) |
| File layout, test names, and groups | [`rules/naming.md`](rules/naming.md) |
| Arrange-act-assert and choosing the correct assertion | [`rules/assertions.md`](rules/assertions.md) |
| Endpoint coverage, authentication, authorization, tenant isolation, validation, and browser tests | [`rules/endpoint-tests.md`](rules/endpoint-tests.md) |
| Factories, test data ownership, and repeated input values | [`rules/test-data.md`](rules/test-data.md) |
| Fakes, mocks, outbound HTTP, time, randomness, and databases | [`rules/isolation.md`](rules/isolation.md) |
| Escaping, injection, cross-tenant access, and privilege checks | [`rules/security.md`](rules/security.md) |
| Environment and CI settings for a slow suite | [`rules/performance.md`](rules/performance.md) |
| Reviewing a test or suite | [`rules/review.md`](rules/review.md) |
