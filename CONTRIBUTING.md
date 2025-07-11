# Contributing

Thank you for considering contributing to Laravel DTO! This document outlines the process for contributing to this project.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

- Use a clear and descriptive title
- Describe the exact steps which reproduce the problem
- Provide specific examples to demonstrate the steps
- Describe the behavior you observed after following the steps
- Explain which behavior you expected to see instead and why
- Include Laravel version, PHP version, and package version

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- Use a clear and descriptive title
- Provide a step-by-step description of the suggested enhancement
- Provide specific examples to demonstrate the steps
- Describe the current behavior and explain which behavior you expected to see instead
- Explain why this enhancement would be useful

### Pull Requests

- Fill in the required template
- Do not include issue numbers in the PR title
- Include screenshots and animated GIFs in your pull request whenever possible
- Follow the PHP and Laravel coding standards
- Include thoughtfully-worded, well-structured tests
- Document new code
- End all files with a newline

## Development Process

### Prerequisites

- PHP 8.1 or higher
- Composer
- Laravel knowledge

### Setting Up Development Environment

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/laravel-dto.git`
3. Install dependencies: `composer install`
4. Create a branch for your feature: `git checkout -b feature/your-feature-name`

### Running Tests

```bash
composer test
```

### Code Quality

Before submitting a pull request, ensure your code passes all quality checks:

```bash
# Run tests
composer test

# Run static analysis
composer analyse

# Fix code style
composer format
```

### Coding Standards

- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Add comprehensive PHPDoc comments
- Use type hints for all parameters and return types
- Write tests for all new functionality

### Commit Messages

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters or less
- Reference issues and pull requests liberally after the first line

### Testing

- Write tests for all new functionality
- Ensure all tests pass before submitting
- Test with different Laravel versions when applicable
- Include both unit and integration tests where appropriate

## Package Structure

Understanding the package structure will help you contribute effectively:

```
src/
├── Commands/           # Artisan commands
├── Generators/         # DTO generation logic
├── Parsers/           # Form Request parsing
├── Templates/         # Code templates
└── LaravelDtoServiceProvider.php
```

## Types of Contributions

### Bug Fixes

- Follow the existing code style
- Include tests that cover the bug
- Update documentation if necessary

### New Features

- Discuss the feature in an issue first
- Follow the existing architecture patterns
- Include comprehensive tests
- Update documentation and examples

### Documentation

- Fix typos and improve clarity
- Add examples for complex features
- Keep the README up to date

## Questions?

Feel free to open an issue for any questions about contributing!
