# Contributing to Pancake 🥞

First off, thank you for considering contributing to Pancake! Your input and contributions help us make this library better for everyone. This document outlines the process and guidelines for contributing to the project.

## Getting Started 🚀

1. **Fork the repository**: Visit [Pancake on GitHub](https://github.com/guibranco/pancake) and click the `Fork` button.
2. **Clone your fork**: Use the following command to clone the repository locally:
   ```bash
   git clone https://github.com/<your-username>/pancake.git
   ```
3. **Install dependencies**: Run the following command to install the necessary dependencies via Composer:
   ```bash
   composer install
   ```
4. **Create a branch**: Create a new branch for your feature or bug fix:
   ```bash
   git checkout -b <feature-or-bugfix-name>
   ```

## Development Guidelines 🛠️

### Code Style ✍️

- Follow the [PHP-FIG PSRs](https://www.php-fig.org/psr/) (e.g., PSR-1, PSR-12).
- Ensure your code is clean and readable.
- Use meaningful variable and method names.

### Compatibility 💻

- Pancake targets **PHP 8+** but aims to maintain compatibility with lower PHP versions when feasible. Test your changes across multiple PHP versions if possible.

### Testing 🧪

- We use [PHPUnit](https://phpunit.de/) for unit testing. Add tests for any new features or bug fixes.
- Ensure that the library maintains at least **80% code coverage**.
- Run tests locally using:
  ```bash
  ./vendor/bin/phpunit
  ```

### Documentation 📖

- Update documentation in the `docs/` directory and any relevant markdown files if your changes affect functionality.
- Documentation is published via GitHub Pages, so ensure all updates are clear and concise.

### Composer Support 🎵

- The library is distributed via Composer. Ensure that `composer.json` reflects accurate metadata and dependencies for your changes.

## Submitting Your Contribution 📨

1. **Commit your changes**: Follow good commit message practices. Example:
   ```
   [FEATURE] Add new Pancake flipping method
   ```
2. **Push your branch**: Push your branch to your forked repository:
   ```bash
   git push origin <feature-or-bugfix-name>
   ```
3. **Create a Pull Request (PR)**: Go to the original [Pancake repository](https://github.com/guibranco/pancake) and open a pull request.

### Pull Request Checklist ✅

- [x] Code follows the PSRs and project standards.
- [x] All tests pass with PHPUnit.
- [x] Code coverage is at least 80%.
- [x] Documentation has been updated (if required).
- [x] PR description clearly explains the purpose and changes.

## Reporting Issues 🐞

If you find a bug or have a feature request, please open an issue in the [GitHub Issues](https://github.com/guibranco/pancake/issues) section. Include as much detail as possible:

- Steps to reproduce (for bugs)
- Use cases and rationale (for features)
- Environment details (e.g., PHP version, OS)

## Community and Support 🤝

If you have any questions or need help contributing, feel free to reach out by opening a discussion in the repository. We're happy to assist!

Thank you for contributing to Pancake! 🥞 Together, we can build a better library.

