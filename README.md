# wp-discourse

This WordPress plugin allows you to **use Discourse as a community engine for your WordPress blog**.

## Features

* Optionally publish all new posts to Discourse automatically
* Use Discourse to comment on blog posts with associated Discourse topics
* Periodically sync the "best" posts in Discourse topics back to the associated WordPress blog entry as WordPress comments
* Enable SSO to Discourse
* Define format of post on Discourse
* Set username of post on Discourse
* Set published category on Discourse
* Allow author to automatically track published Discourse topics
* Show comments on Discourse based on post score and commenter trust level

## Installation

### Plugin manager from your `wp-admin`

Download the [latest release](https://github.com/discourse/wp-discourse/releases) and upload it in the WordPress admin from Plugins > Add New > Upload Plugin.

### Composer

If you're using Composer to manage WordPress, add WP-Discourse to your project's dependencies. Run:

```sh
composer require discourse/wp-discourse 0.7.0
```

Or manually add it to your `composer.json`:

```json
"require": {
  "php": ">=5.3.0",
  "wordpress": "4.1.1",
  "discourse/wp-discourse": "0.7.0"
}
```

## Contributing

1. Fork this repo
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create a new pull request
