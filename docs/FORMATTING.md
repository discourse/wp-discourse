### WP Discourse Code Formatting

WP Discourse uses [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) to handle code formatting (``phpcs``). The ``phpcs`` configuration is handled in the ``.phpcs.xml`` file, a type of [Annotated Ruleset](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-Ruleset).

#### Formatting

To format your code, run ``phpcs`` to see what issues the file has.

```
vendor/bin/phpcs lib/discourse-publish.php
```

All issues at "warning" level and above should be addressed. It is recommended that you fix issues manually, as use of ``phpcbf`` can lead to unexpected results.

