### WP Discourse Composer Development Usage

WP Discourse uses [Composer](https://getcomposer.org) in the standard fashion, but there are a few things that are worth pointing out.

### Testing and Formatting

The relevant composer packages for testing and formatting are largely determined by the requirements of Wordpress, and the PHP versions this plugin supports. Those dependencies should only be changed if a compatibility review is first conducted.

```
"squizlabs/php_codesniffer"
"phpunit/phpunit"
"phpunit/php-code-coverage"
"phpcompatibility/php-compatibility"
"wp-coding-standards/wpcs"
```

Any changes to these packages should be accompanied by both a compatibility review and a review of the formatting.md and tests.md documentation.

### Using Composer Packages in Production

There are various methods used to handle composer packages in a production Wordpress plugin. Two approaches worth highlighting for context are those used by [Yoast](https://developer.yoast.com/blog/safely-using-php-dependencies-in-the-wordpress-ecosystem/) and [Delicious Brains](https://deliciousbrains.com/php-scoper-namespace-composer-depencies/), both of which use namespacing with a custom build process. We use a simplified version of those approaches, which we are aiming to script over time.

Our approach is to:

1. Isolate and namespace vendor packages used in production (namespaced in ``WPDiscourse``).
2. Define a build and autoload process to ensure ``1`` works with both Wordpress.org installation (via the plugin marketplace) and composer installation.

The goal here is simplicity and selectivity. We do not want to auto-namespace the entire plugin, or every development dependency. Efficiency is not necessarily the goal here. It's better to be slower and more selective, than faster and more generalized in this respect.

#### Step 1. Add package as a development dependency

Add whatever package you want to use in production as a development dependency, for example

```
"require-dev": {
  ...
  "monolog/monolog": "^1.25"
}
```

Then run ``composer install`` to install your package in ``vendor``.

#### Step 2. Build a distribution version of the package

##### 2.1 Setup

First, install [``humbug/php-scoper``](https://github.com/humbug/php-scoper) globally on your machine. A local global install for development is cleaner than a project install via ``bamarni/composer-bin-plugin`` for our purposes.

Then, update the finders ``path`` array in ``scoper.inc.php`` to include your package(s) in the scoping. Make sure you include each package required in production, for example

```
['monolog', '/^psr/']
```

will include both the ``monolog`` and ``psr`` packages. ``psr`` is a production dependency of ``monolog``.

You may also need to perform modifications on the package files in order to achieve compatibility. For example, monolog

##### 2.2 Running

Now run ``add-prefix`` as follows

```
php-scoper add-prefix --output-dir=./vendor_namespaced/ --force
```

This will populate ``vendor_namespaced`` with namespaced versions of the packages matching the listed paths, with any patchers applied.

#### Step 3. Use the namespaced package in your code

When using the package in the plugin, use the version in ``vendor_namespaced``, which is namespaced with ``WPDiscourse``. For example, use

```
\WPDiscourse\Monolog\Logger
```
not

```
\Monolog\Logger
```

The non-namespaced version will still be present in development (in your ``vendor`` folder), but shouldn't be used, and won't be bundled in the production build.

#### Step 4. Build for production

When building for production, use composer as you normally would when preparing a production build of a Wordpress plugin, i.e. by installing optimized non-development packages

```
composer install --prefer-dist --optimize-autoloader --no-dev
```

This will also add the namespaced packages in ``vendor_namespaced`` to the autoloaded due to the autoload classmap:

```
"autoload": {
  "classmap": [
    "vendor_namespaced/"
  ]
}
```
You can see a full list of autoloaded classes in ``vendor/composer/autoload_classmap.php``.

Once the production dependencies are installed, the plugin should then be bundled for submission.




