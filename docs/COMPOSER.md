### WP Discourse Composer Development Usage

WP Discourse uses [Composer](https://getcomposer.org) in the standard fashion, but there are a few things that are worth pointing out about using composer packaages in production.

### Using Composer Packages in Production

This approach is inspired by approaches used by [Yoast](https://developer.yoast.com/blog/safely-using-php-dependencies-in-the-wordpress-ecosystem/) and [Delicious Brains](https://deliciousbrains.com/php-scoper-namespace-composer-depencies/), both of which use namespacing with a custom build process. We use a simplified version of that approach, which we are scripting over time. In short, we:

1. isolate and namespace (``WPDiscourse``) vendor packages used in production; and
2. define a build and autoload process to ensure ``1`` works with both wordpress.org and composer installation.

The goal here is simplicity and selectivity. We do not want to auto-namespace the entire plugin, or every development dependency.

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

Then, create a finder in the 'finders' array in ``scoper.inc.php`` to include your package(s) in the scoping. See the [documentation on Symfony Finders](https://symfony.com/doc/current/components/finder.html) for details on usage.

You may also need to perform modifications on the package files in order to achieve compatibility. For example, monolog requires various function signature parsing and type declaration removals. This can be performed in the 'patchers' callback.

##### 2.2 Running

Now run ``add-prefix`` as follows

```
php-scoper add-prefix --output-dir=./vendor_namespaced/ --force
```

This will populate ``vendor_namespaced`` with namespaced versions of the packages matching the listed paths, with any patchers applied.

#### Step 3. Use the namespaced package in your code

When using the package in the plugin code, use the version in ``vendor_namespaced``, which is namespaced with ``WPDiscourse``. For example, use

```
\WPDiscourse\Monolog\Logger
```
not

```
\Monolog\Logger
```

The non-namespaced version will still be present in development (in your ``vendor`` folder), but shouldn't be used in the plugin code, and won't be bundled in the production build.

#### Step 4. Build for production

When building for production, use composer as you normally would when preparing a production build of a Wordpress plugin, i.e. by installing optimized non-development packages

```
composer install --prefer-dist --optimize-autoloader --no-dev
```

This will also add the namespaced packages in ``vendor_namespaced`` to the autoload due to the autoload classmap:

```
"autoload": {
  "classmap": [
    "vendor_namespaced/"
  ]
}
```
You can see a full list of autoloaded classes in ``vendor/composer/autoload_classmap.php``.

Once the production dependencies are installed, the plugin should then be bundled for submission.




