# Contributing to Zwernemann_Withdrawl

Thank you for your interest in contributing to Zwernemann_Withdrawl.

Zwernemann_Withdrawl is an open source Magento 2 extension that combines conversational AI, semantic product search and Magento commerce functionality. Contributions, bug reports, feature ideas and improvements are welcome.

## Before You Start

Please check the existing issues and discussions before opening a new issue or submitting a pull request. There may already be an issue covering the same topic.

For larger changes, especially changes to the conversation processing, AI integration, product search or commerce tools, please open an issue or discussion first. This helps to discuss the proposed approach before significant development work begins.

## Reporting Bugs

Please use the bug report template when reporting a problem.

A useful bug report should include:

* Magento version
* PHP version
* Zwernemann_Withdrawl version
* Relevant configuration
* AI provider and model, where relevant
* Steps to reproduce the problem
* Expected behaviour
* Actual behaviour
* Relevant log messages or stack traces

Please remove passwords, API keys, customer data, order information and other sensitive information before submitting logs or configuration files.

## Suggesting Features

Feature requests are welcome.

Please describe:

* The problem you are trying to solve
* Why the feature would be useful
* How you would expect it to work
* Any alternative solutions you have considered

For larger features, please open a discussion before starting implementation.

## Pull Requests

Pull requests should focus on one specific change whenever possible.

Before submitting a pull request:

1. Make sure your branch is based on the current `main` branch.
2. Keep the changes focused and avoid unrelated modifications.
3. Follow the existing coding style and Magento conventions.
4. Update documentation where necessary.
5. Add or update tests where appropriate.
6. Test the changes with a supported Magento installation.
7. Make sure existing functionality continues to work.

Please provide a clear description of what the pull request changes and why the change is necessary.

## Magento Compatibility

Changes should be compatible with the Magento 2 versions supported by the current release.

If a change requires a specific Magento version, PHP version or third party service, please state this clearly in the pull request.

## AI and External Services

Zwernemann_Withdrawl can use external services such as Anthropic Claude, Voyage AI and Pinecone.

Changes involving AI prompts, model interaction, embeddings, semantic search or external APIs should be made carefully. Please consider:

* API costs
* token usage
* response reliability
* error handling
* privacy and data protection
* behaviour when an external service is unavailable

Do not commit API keys, credentials or other secrets to the repository.

## Security

Please do not report security vulnerabilities through public GitHub issues.

For security related problems, please follow the instructions in [SECURITY.md](SECURITY.md).

## Coding Style

Please follow the existing coding style and structure of the project.

Magento service contracts and Magento's established dependency injection and architectural patterns should be preferred over direct access to implementation details where appropriate.

Changes should be kept as simple as possible and should not introduce dependencies unless there is a clear reason to do so.

## Documentation

If a change affects configuration, installation, administration or user-visible functionality, please update the documentation accordingly.

Documentation improvements are also welcome as independent contributions.

## License

By contributing to this project, you agree that your contributions will be licensed under the same license as the project.

Please see the [LICENSE](LICENSE) file for details.

## Questions and Discussions

If you are unsure whether an idea is suitable for the project, feel free to open a GitHub Discussion before starting development.

Thank you for helping improve Zwernemann_Withdrawl.
