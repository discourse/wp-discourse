<?php
/**
 * Plugin Name: WP-Discourse
 * Description: Use Discourse as a community engine for your WordPress blog
 * Version: 1.2.1
 * Author: Discourse
 * Text Domain: wp-discourse
 * Domain Path: /languages
 * Author URI: https://github.com/discourse/wp-discourse
 * Plugin URI: https://github.com/discourse/wp-discourse
 * GitHub Plugin URI: https://github.com/discourse/wp-discourse
 *
 * @package WPDiscourse
 */

/**  Copyright 2014 Civilized Discourse Construction Kit, Inc (team@discourse.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

define( 'WPDISCOURSE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPDISCOURSE_URL', plugins_url( '', __FILE__ ) );
define( 'MIN_WP_VERSION', '4.4' );
define( 'MIN_PHP_VERSION', '5.4.0' );
define( 'WPDISCOURSE_VERSION', '1.2.1' );

require 'class-autoload-discourse-classes.php';

require 'lib/utilities.php';
require 'templates/html-templates.php';
require 'templates/template-functions.php';
require 'lib/discourse.php';
require 'lib/settings-validator.php';
require 'lib/admin.php';
require 'lib/sso.php';
require 'lib/wordpress-email-verification.php';
require 'lib/discourse-sso.php';
require 'lib/discourse-publish.php';
require 'lib/discourse-comment.php';
require 'lib/meta-box.php';
require 'lib/shortcodes/sso-client.php';
require 'lib/sso-login-form.php';
require 'lib/sso/sso-url.php';
require 'lib/sso/button-markup.php';

$discourse_settings_validator = new WPDiscourse\Validator\SettingsValidator();
$discourse                    = new WPDiscourse\Discourse\Discourse();
$discourse_admin              = new WPDiscourse\DiscourseAdmin\DiscourseAdmin();
$discourse_publisher          = new WPDiscourse\DiscoursePublish\DiscoursePublish();
$discourse_comment            = new WPDiscourse\DiscourseComment\DiscourseComment();
$wordpress_email_verifier     = new WPDiscourse\WordPressEmailVerification\WordPressEmailVerification( 'discourse_email_verification_key', 'discourse' );
$discourse_sso                = new WPDiscourse\DiscourseSSO\DiscourseSSO( $wordpress_email_verifier );
$discourse_publish_metabox    = new WPDiscourse\MetaBox\MetaBox();

// refactored classes that use autoload.
$discourse_external_sso       = new WPDiscourse\sso\Client();
$discourse_query_redirect = new WPDiscourse\sso\QueryRedirect();

register_activation_hook( __FILE__, array( $discourse, 'install' ) );
