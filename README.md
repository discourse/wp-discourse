# WP Discourse
[![OpenCollective](https://opencollective.com/wp-discourse/backers/badge.svg)](#backers)
[![OpenCollective](https://opencollective.com/wp-discourse/sponsors/badge.svg)](#sponsors)

**Note:** the wp-discourse plugin requires >= PHP-5.4.0.

The WP Discourse plugin acts as an interface between your WordPress site and your
[Discourse](http://www.discourse.org/) community.

The plugin is not a substitute for Disqus-like commenting systems.

### Use Discourse for Comments:

- Automatically creates a forum topic for discussion when a new blog post is published.
- Associates WP author accounts with their respective Discourse accounts. Does not require DiscourseConnect.
- Replies from the forum discussion can be embedded in the WP blog post. Select which replies to display
based on post score and commenter "trust level" -- see docs.

#### See it Live

- [blog.discourse.org](http://blog.discourse.org/)
- [boingboing.net](http://boingboing.net/)

### DiscourseConnect

The plugin also comes with optional DiscourseConnect functionality which lets you use your WordPress site as the DiscourseConnect provider for your Discourse forum.

This will override Discourse's native (and powerful) login flow and is only recommended for use cases
that strictly require such a setup, e.g. a site that is already using WordPress for large scale user management.

### Contact

Please post bug reports, support requests and all other inquiries in our [dedicated support forum](https://meta.discourse.org/c/support/wordpress)

### Installation

#### From your WordPress dashboard

1. Visit 'Plugins > Add New'
2. Search for 'WP Discourse'
3. Activate WP Discourse from your Plugins page

#### From wordpress.org

1. Download WP Discourse
2. Upload the 'wp-discourse' directory to your '/wp-content/plugins/' directory
3. Activate WP Discourse from your Plugins page

#### With Composer

If you're using Composer to manage WordPress, add WP-Discourse to your project's dependencies. Run:

```sh
composer require discourse/wp-discourse ~1.3.2
```

Or manually add it to your `composer.json`:

```json
{
  "require": {
    "php": ">=5.4.0",
    "discourse/wp-discourse": "~1.3.2"
  }
}
```

For more detailed instructions please see the [WP Discourse plugin installation and setup](https://meta.discourse.org/t/wp-discourse-plugin-installation-and-setup/50752).
### Frequently Asked Questions

#### Does this plugin install Discourse for me?

No this plugin acts as an interface between Discourse and WordPress. For it to work you will need to first set up
Discourse forum. You can install Discourse for yourself following either of these guides:

- [Install Discourse in Under 30 Minutes](https://github.com/discourse/discourse/blob/master/docs/INSTALL-cloud.md)
- [How to use the Discourse One-Click Application on DigitalOcean](https://www.digitalocean.com/community/tutorials/how-to-use-the-discourse-one-click-application-on-digitalocean)

#### Can I import old WordPress comments as Discourse comments (i.e. "replies")?

No.

#### Do WordPress and Discourse have to be installed on the same server?

The plugin uses the Discourse API, so your forum and blog can be hosted separately and the integration will still work.
In fact, we strongly recommend hosting the two applications separately, since their hosting requirements are very different.

#### Is it possible to customize the comment templates?

Yes, the html templates used for publishing posts on Discourse and for displaying comments on WordPress can be customized in your theme.
This is done by hooking into the filters that are applied to each template.

For more details on template customization, see: [WP Discourse template customization](https://meta.discourse.org/t/wp-discourse-template-customization/50754)

#### Contributing

Before you start contributing or developing this plugin please read the documentation in ``docs/``, starting with [contributing.md](https://github.com/discourse/wp-discourse/blob/main/docs/contributing.md). If you have any questions about those process, please post in [#dev](https://meta.discourse.org/c/dev/7) for assistance.
