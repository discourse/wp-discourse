### WP Discourse Code Formatting Guide

WP Discourse uses
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) to handle code formatting (``phpcs``);
- the native PHP syntax checker; and
- jshint for javascript.

These formatters will be applied on each pull request in Github Actions (via ``.github/workflows/ci.yml``), so make sure you run them locally before making your PR.

#### PHPCS

The ``phpcs`` configuration is handled in the ``.phpcs.xml`` file, a type of [Annotated Ruleset](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-Ruleset). Install the development composer packages by running ``composer install`` prior to using ``phpcs``, and run it using ``vendor/bin/phpcs``, for example

```
vendor/bin/phpcs lib/discourse-publish.php
```

All errors must be addressed, and all warnings should be addressed as far as possible. You can attempt to fix any issues automatically using ``phpcbf``. If using ``phpcbf``

1. Make sure your working tree is clean as you may wish to revert the results (in some cases it can create more issues than it solves)

2. Only use ``phpcbf`` on a file by file basis.

#### Native Syntax Check

The ``.github/workflows/ci.yml`` applies a syntax check for each supported version of PHP by searching for all ``.php`` files in the repository, running the relevant version of the PHP interpreter, and catching syntax errors via  ``xargs``:

```
find -L . -name '*.php' -not -path "./vendor/*" -print0 | xargs -0 -n 1 -P 4 php -l
```

To perform this locally:

1. First, check the PHP version "matrix" in ``.github/workflows/ci.yml`` to see which versions we are currently testing.

2. Then you'll need a local build of each PHP version. You can install different PHP versions using ``phpbrew`` or ``phpenv`` (we may include a ``phpenv`` setup in the project in the future).

3. Finally run the above command using each PHP version in the matrix.

All errors must be fixed, even if they are not in a file required in production, and all warnings must be addressed as far as possible.

#### JSHint

First, install ``jshint`` globally on your machine

```
npm install -g jshint
```

Then run it in the ``wp-discourse`` directory

```
jshint .
```
