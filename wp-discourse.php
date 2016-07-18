<?php
/**
 * Plugin Name: WP-Discourse
 * Description: Use Discourse as a community engine for your WordPress blog
 * Version: 0.9.0
 * Author: Sam Saffron, Robin Ward
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

require_once( __DIR__ . '/lib/utilities.php' );
require_once( __DIR__ . '/lib/html-templates.php' );
require_once( __DIR__ . '/lib/discourse.php' );
require_once( __DIR__ . '/lib/settings-validator.php' );
require_once( __DIR__ . '/lib/admin.php' );
require_once( __DIR__ . '/lib/sso.php' );
require_once( __DIR__ . '/lib/wordpress-email-verification.php' );
require_once( __DIR__ . '/lib/discourse-sso.php' );
require_once( __DIR__ . '/lib/discourse-publish.php' );
require_once( __DIR__ . '/lib/discourse-comment.php' );
require_once( __DIR__ . '/lib/meta-box.php' );
require_once( __DIR__ . '/lib/plugin-support/woocommerce-support.php' );

$discourse_settings_validator = new WPDiscourse\Validator\SettingsValidator();
$discourse                    = new WPDiscourse\Discourse\Discourse();
$discourse_admin              = new WPDiscourse\DiscourseAdmin\DiscourseAdmin();
$discourse_publisher          = new WPDiscourse\DiscoursePublish\DiscoursePublish();
$discourse_comment            = new WPDiscourse\DiscourseComment\DiscourseComment();
$wordpress_email_verifier     = new WPDiscourse\WordPressEmailVerification\WordPressEmailVerification( 'discourse_email_verification_key', 'discourse', 'wp-discourse' );
$discourse_sso                = new WPDiscourse\DiscourseSSO\DiscourseSSO( $wordpress_email_verifier );
$discourse_publish_metabox    = new WPDiscourse\MetaBox\MetaBox();
$discourse_woocommerce        = new WPDiscourse\PluginSupport\WooCommerceSupport();

register_activation_hook( __FILE__, array( $discourse, 'install' ) );
