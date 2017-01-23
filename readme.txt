=== WP Discourse ===
Contributors: cdck, retlehs, samsaffron, scossar, techapj
Tags: discourse, forum, comments, sso
Requires at least: 4.4
Tested up to: 4.7
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to use Discourse as a community engine for your WordPress website.

== Description ==

The WP Discourse plugin acts as an interface between your WordPress site and your
[Discourse](http://www.discourse.org/) community.

###Use Discourse for comments:

- Automatically creates a forum topic for discussion when a new blog post is published.
- Associates WP author accounts with their respective Discourse accounts. Does not require SSO.
- Replies from the forum discussion can be embedded in the WP blog post. Select which replies to display
based on post score and commenter "trust level" -- see docs.

#### See it live

- [blog.discourse.org](http://blog.discourse.org/)
- [boingboing.net](http://boingboing.net/)
- [howtogeek.com](http://www.howtogeek.com/)

###Single Sign On

The plugin also comes with optional SSO functionality which lets you use your WordPress site as the
Single Sign On provider for your Discourse forum.

This will override Discourse's native (and powerful) login flow and is only recommended for use cases
that strictly require such a setup, e.g. a site that is already using WordPress for large scale user management.

###Note

The WP Discourse plugin requires PHP version 5.4.0 and greater. If >=PHP-5.4.0 is not available, the plugin installation
will fail.

###Contact

- The plugin is being developed by [scossar](https://github.com/scossar) on behalf of the Discourse team.

- Bug reports and other developer inquiries should be directed at our GitHub Issues:
[https://github.com/discourse/wp-discourse/issues](https://github.com/discourse/wp-discourse/issues)

- Please post support requests to our [dedicated support forum](https://meta.discourse.org/c/support/wordpress)

== Installation ==

#### From your WordPress dashboard

1. Visit 'Plugins > Add New'
2. Search for 'WP Discourse'
3. Activate WP Discourse from your Plugins page

#### From wordpress.org

1. Download WP Discourse
2. Upload the 'wp-discourse' directory to your '/wp-content/plugins/' directory
3. Activate WP Discourse from your Plugins page

For more detailed instructions please see the [setup](https://github.com/discourse/wp-discourse/wiki/Setup) page of the
[wp-discourse wiki](https://github.com/discourse/wp-discourse/wiki)

== Frequently Asked Questions ==

= Does this plugin install Discourse for me? =

No this plugin acts as an interface between Discourse and WordPress. For it to work you will need to first set up
Discourse forum. You can install Discourse for yourself following either of these guides:

- [Install Discourse in Under 30 Minutes](https://github.com/discourse/discourse/blob/master/docs/INSTALL-cloud.md)
- [How to use the Discourse One-Click Application on DigitalOcean](https://www.digitalocean.com/community/tutorials/how-to-use-the-discourse-one-click-application-on-digitalocean)

= Can I import old WordPress comments as Discourse comments (i.e. "replies")? =

No.

= Do WordPress and Discourse have to be installed on the same server? =

The plugin uses the Discourse API, so your forum and blog can be hosted separately and the integration will still work.
In fact, we strongly recommend hosting the two applications separately, since their hosting requirements are very different.

= Is it possible to customize the comment templates? =

Yes, the html templates used for publishing posts on Discourse and for displaying comments on WordPress can be customized in your theme.
This is done by hooking into the filters that are applied to each template.

For more details on template customization, take a look at this section of our wiki: [Template Customization](https://github.com/discourse/wp-discourse/wiki/Template-Customization)

== Screenshots ==

1. Select whether a post is to be published to Discourse, and what category it is to be published into.

2. A WordPress posts with no comments.

3. Adding a comment on the Discourse forum.

4. The comment appears on WordPress.

== Changelog ==

**Note:** The wp-discourse plugin requires >=PHP-5.4.0 to be running on your server.

**Note:** Have you made changes to the HTML templates? The template changes are no longer handled from the plugin
admin, They must be customized with filters. see the [Template Customization](https://github.com/discourse/wp-discourse/wiki/Template-Customization)
section of the [wiki](s://github.com/discourse/wp-discourse/wiki) for details.

#### 1.1.3 22/01/2017

- Display saved meta-box values for scheduled and draft posts

#### 1.1.2 12/12/2016

- Test against WordPress version 4.7

#### 1.1.0 16/11/2016

- Halt plugin installation if >= PHP-5.4.0 is not available
- Halt plugin installation WordPress version is < 4.4.0
- Don't override WordPress new-user notification email when SSO is not enabled
- Delete old `discourse` options on plugin uninstall if it is still in the database

#### 1.0.2 12/10/2016

- Don't call `get_date_from_gmt` on `datetime` returned from Discourse
- Add WordPress comments_number to Discourse comments_number when both are used
- Add `'.screen-reader-text'` class to screen-reader text
- Add `'.discourse-comments-area'` class to Discourse comments area
- Add filter to Discourse categories before displaying them in the 'Publish to Discourse' meta-box

#### 1.0.1 10/10/2016

- Fix: Don't call `array_key_exists` on null
- Fix: Don't logout from Discourse when SSO is not enabled

#### 1.0.0 26/09/16

- Move WooCommerce support into a separate plugin, see the wiki page [WooCommerce Support](https://github.com/discourse/wp-discourse/wiki/WooCommerce-Support)
for more details
- Break settings page into tabbed sections
- Add a settings section for customizing all user facing text
- Display the Discourse username instead of the fullname in the default comments template
- Add a 'Participants' heading
- Don't display the category-select option in posts that have already been published to Discourse
- Only hook into the WordPress `comments_number` hook when both Discourse and WordPress comments are used for a post
- Sync changed post title with Discourse
- Get options more efficiently
- Add a longer sync period for comments on archive pages
- Add a 'Discourse link text' option
- Copy 'discourse' options to the new option_groups when upgrading from versions < 1.0.0
- Delete the 'discourse' option when upgrading from versions < 1.0.0
- Improve option descriptions

#### 0.9.9 13/09/16

- Return 'discourse_comments_count' from WordPress `get_comments_number` function when WordPress comments are not being used
- Disable publishing to Discourse when a WordPress post is published through `XML-RPC`
- Strip html tags from WordPress titles before sending them to Discourse

#### 0.9.8 09/09/16

- Fix: Retrieve private categories for categories list

#### 0.9.7.5 04/09/16

- Security update

#### 0.9.7 27/08/16

- Set expiration time on `sync_to_discourse` and `sync_comments` lock transients

#### 0.9.6 18/08/16

- Set `require_activation => 'true'` for SSO request after email address change on WordPress
- Sync logout from WordPress with Discourse

#### 0.9.5 16/08/16

- Require activation on Discourse when email address can't be verified by WordPress
- Tested up to WordPress 4.6

#### 0.9.4 16/08/10

- Fix: WordPress comment box showing when there are no WordPress comments

#### 0.9.3 16/07/21

- Fix: existing WordPress comments always showing
- Fix: youtube data attribute being escaped by wp_kses_post
- Substitute comment url for comment_url tag
- Internationalize comments_number function

#### 0.9.2 16/07/20

- Partial fix for emoji relative paths

#### 0.9.1 16/07/19

- Add option to redirect to Discourse without login
- Log error if validation filter is missing
- Check for post_types array before trying to access it

#### 0.9.0 16/07/18

- Verify email before logging into Discourse

#### 0.7.0 16/05/16

- Restructure code
- Move templates out of options
- Validate settings
- Add notices to indicate connection status
- Sanitize admin options page
- Sanitize comment template output
- Add type argument to text input method
- Use cached categories when there is a configuration error
- Fix name property not available in participants array
- Use `wp_get_current_user`
- Fix `add_query_arg` undefined offset notice
- Update Discourse post on WP post update
- Better method for including comments script
- Allow choosing Discourse category per post
- Replace avatar URL function
- Fix timezone for custom timestamp
