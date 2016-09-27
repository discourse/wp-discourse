### 0.9.7: August 18th, 2016
* Set expiration on sync_lock transients ([248](https://github.com/discourse/wp-discourse/pull/248))

### 0.9.6: August 16th, 2016
* Sync logout with Discourse ([247](https://github.com/discourse/wp-discourse/pull/247))
* Require user activation after email change ([246](https://github.com/discourse/wp-discourse/pull/246))
* Require user activation ([245](https://github.com/discourse/wp-discourse/pull/245))

### 0.9.4: August 10th, 2016
* Fix existing comment regression ([243](https://github.com/discourse/wp-discourse/pull/243))

### 0.7.0: May 18th, 2016
* Move templates out of options ([#194](https://github.com/discourse/wp-discourse/pull/194))
* Validate settings ([#189](https://github.com/discourse/wp-discourse/pull/189))
* Add notices to indicate connection status ([#193](https://github.com/discourse/wp-discourse/pull/193))
* Sanitize admin options page ([#196](https://github.com/discourse/wp-discourse/pull/196))
* Sanitize comment template output ([#195](https://github.com/discourse/wp-discourse/pull/195))
* Add type argument to text input method ([#192](https://github.com/discourse/wp-discourse/pull/192))
* Use cached categories when there is a configuration error ([#191](https://github.com/discourse/wp-discourse/pull/191))
* Fix name property not available in participants array ([#187](https://github.com/discourse/wp-discourse/pull/187))
* Use `wp_get_current_user` ([#185](https://github.com/discourse/wp-discourse/pull/185))
* Fix `add_query_arg` undefined offset notice ([#184](https://github.com/discourse/wp-discourse/pull/184))
* Update Discourse post on WP post update ([#176](https://github.com/discourse/wp-discourse/pull/176))
* Better method for including comments script and other small tweaks ([#181](https://github.com/discourse/wp-discourse/pull/181))
* Allow choosing Discourse category per post ([#177](https://github.com/discourse/wp-discourse/pull/177))
* Replace avatar URL function ([#172](https://github.com/discourse/wp-discourse/pull/172))
* Fix timezone for custom timestamp ([#162](https://github.com/discourse/wp-discourse/pull/162))

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
