### WP Discourse Contribution Guidelines

This is an overview guide for WP Discourse plugin development, pull requests and deployment.

### Development

Before starting development, make sure you read [composer.md](https://github.com/discourse/wp-discourse/blob/main/docs/composer.md), [formatting.md](https://github.com/discourse/wp-discourse/blob/main/docs/formatting.md) and [tests.md](https://github.com/discourse/wp-discourse/blob/main/docs/tests.md) to understand how each of those subjects are handled. If you feel those guides are not descriptive enough, or you're stuck with one of them, please post in [#dev](https://meta.discourse.org/c/dev/7) for assistance.

All changes to the WP Discourse codebase need to come with accompanying PHPUnit tests. We recommend you use test-driven development, particularly if you're writing new features. All changes must also support the PHP versions the plugin supports, which are listed in the [formatting workflow](https://github.com/discourse/wp-discourse/blob/main/.github/workflows/formatting.yml), however the most common PHP version to work with locally is ``PHP 7.4``. Note that ``PHP 8.0`` introduces some new syntax that is incompatible with older versions.

### Pull Requests

All changes to the WP Discourse plugin are done via pull requests. The [formatting](https://github.com/discourse/wp-discourse/blob/main/.github/workflows/formatting.yml), [metadata](https://github.com/discourse/wp-discourse/blob/main/.github/workflows/metadata.yml) and [tests](https://github.com/discourse/wp-discourse/blob/main/.github/workflows/formatting.yml) workflows will be run against all pull requests. They will:

- Check the syntax of ``.php`` files against the PHP versions we support.
- Run PHPCS against all ``.php`` files, failing on errors.
- Run JSHint against all ``.js`` files.
- Run all of the PHPUnit tests (both standard and multisite).
- Check that the all the version numbers have been increased and release notes have been added.

#### Versioning and Release Notes

All pull requests need to bump the version of the plugin; in most cases the ``PATCH``. All version increments need release notes in the readme.txt.

### Deployment

Once a pull request is ready and CI is passing, a Discourse staff member will need to review, approve and merge the PR. Once the PR is merged, the staff member will create a new git tag matching the new version of the Plugin. Creating the tag will trigger the [deploy](https://github.com/discourse/wp-discourse/blob/main/.github/workflows/deploy.yml) workflow which pushes the new version to wordpress.org.
