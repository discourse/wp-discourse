### 0.6.6: July 30th, 2015
* Add custom datetime format string to admin settings ([#160](https://github.com/discourse/wp-discourse/pull/160))
* Add a log entry when HTTP request fails ([#159](https://github.com/discourse/wp-discourse/pull/159))
* Log out of WordPress when logging out of Discourse ([#158](https://github.com/discourse/wp-discourse/pull/158))
* Fix security issue, add missing `esc_url_raw()` ([#157](https://github.com/discourse/wp-discourse/pull/157))
* Fix SSO login ([#156](https://github.com/discourse/wp-discourse/pull/156))
* Use `wp_remote_get` instead of `file_get_contents` ([#155](https://github.com/discourse/wp-discourse/pull/155))
* Fix user mention links ([8b6fe46](https://github.com/discourse/wp-discourse/commit/8b6fe46bdbeaa6f4be490723f1e9d6b5a6f48d41))
* Allow showing existing WP comments under Discourse ([#137](https://github.com/discourse/wp-discourse/pull/137))
* Add `<time>` to allowed tags ([#135](https://github.com/discourse/wp-discourse/pull/135))
* Don't do a replace if already an absolute URL ([#131](https://github.com/discourse/wp-discourse/pull/131))

### 0.6.5: February 28th, 2015
* Whitespaces should be stripped only on strings

### 0.6.4: February 24th, 2015
* Minor re-organization of the settings page
* Fetch categories from remote
* Add fixes for allowed post types
* Fix asset URLs on synced Discourse comments

### 0.6.3: January 31st, 2015
* Add CHANGELOG
* Move comments template into new folder
* Move SSO and admin functions into separate files
* Switch from `register_uninstall_hook` to `uninstall.php`
* Move JS from separate file to inline
* Remove unnecessary stylesheet
