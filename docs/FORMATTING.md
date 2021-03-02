### WP Discourse Code Formatting

WP Discourse uses [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) to handle code formatting (``phpcs``). The ``phpcs`` configuration is handled in the ``.phpcs.xml`` file, a type of [Annotated Ruleset](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-Ruleset).

#### Formatting

To format your code, run ``phpcs`` to see what issues the file has.

```
vendor/bin/phpcs lib/discourse-publish.php
```

Ideally, all issues at "warning" level and above should be addressed.

You can attempt to fix the issues automatically using ``phpcbf``, adhering to following:

1. Make sure your working tree is clean as you may wish to revert the results (in some cases it can create more issues than it solves)

2. Only use ``phpcbf`` on a file by file basis.s
