### WP Discourse Tests Guide

WP Discourse uses PHPUnit tests. For general guides on PHPUnit tests, and their application in Wordpress, please see
- The [WP Handbook on Plugin Unit Tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
- The [PHPUnit Documentation](https://phpunit.readthedocs.io)
Please make sure you check the version of the PHPUnit Documentation you're viewing against the version this plugin is using in ``./composer.json``. You can change to any version of the documentation by updating the docs url.

### Setup

#### Files and Database

Before running the tests suite, you need to setup your tests database. You can do this using the following command in the root plugin directory. Make sure you substitute your local root mysql password.

```
cd wp-discourse
bash bin/install-wp-tests.sh wordpress_test root '<enter_your_root_pwd_here>' localhost latest
```
If this command returns an error, clean up anything it created before running it again, i.e. 
```
## Remove the tmp files
rm -rf /path/to/tmp/wordpress
rm -rf /path/to/tmp/wordpress-tests-lib

## Drop the test database
mysql
DROP DATABASE wordpress_test;
FLUSH PRIVILEGES;
exit
```
Make sure that command completes successfully, with no errors, before continuing.

#### Environment and Dependencies

If you haven't already, run ``composer install`` in the root plugin directory to pull in the main tests dependencies. 

##### Xdebug

One additional dependency you need in your environment is the php extension Xdebug. The installation of Xdebug is environment specific, however we would recommend you use the [Xdebug Installation Wizard](https://xdebug.org/wizard) to ensure your installation is correct. For testing purposes Xdebug should be run in ``coverage`` mode, which means that you should have two lines in your ``php.ini`` that look like this

```
zend_extension = /path/to/xdebug.so
xdebug.mode = coverage
```

### Configuration

The tests suite is configured by the ``phpunit.xml`` file. Read more about the elements in the file, and their usage in the [PHPUnit Documentation](https://phpunit.readthedocs.io). Make sure you're viewing the version of the docs that matches the version of PHPUnit being used here, as the elements in that file change significantly between versions.

#### Coverage

The tests coverage whitelist in the config file limits the coverage reports to the classes in the plugin that have tests. This scope should be expanded over time as more classes have tests written for them. To see the overall progress of tests coverage, remove the coverage whitelist elements from the config file.

### Usage

Once you've completed the ``Setup`` section, run the tests suite using

```
vendor/bin/phpunit
```

To run a specific suite add the path to the file as an argument

```
vendor/bin/phpunit tests/test-discourse-publish.php
```

To run a specific test in a suite use the ``--filter`` option

```
vendor/bin/phpunit tests/test-discourse-publish.php --filter=test_sync_to_discourse_when_creating_with_embed_error 
```

To add a coverage report to the output add ``--coverage-text``, which will send the report to stdout. 

```
vendor/bin/phpunit --coverage-text
```

Run ``phpunit --help`` to see other report formats that can be generated (e.g. html or crap4j).




