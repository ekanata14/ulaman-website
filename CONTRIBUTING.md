# Contributing to Gretiva

Thank you for your interest in contributing!

## How to contribute

1. Fork the repository.
2. Create a feature branch: `git checkout -b feat/my-feature`.
3. Make your changes and add or update tests.
4. Ensure the test suite passes: `composer run test`.
5. Open a pull request against `main` with a clear description.

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for formatting. Run `composer run lint` before submitting. The CI pipeline will reject PRs that fail the linter.

## Commit Messages

Use conventional commit prefixes: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`.

## Reporting Bugs

Open a [GitHub Issue](../../issues) with a clear description, steps to reproduce, and your PHP/Laravel version.
