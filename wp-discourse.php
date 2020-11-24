<?php
/**
 * Plugin Name: WP-Discourse
 * Description: Use Discourse as a community engine for your WordPress blog
 * Version: 2.1.9
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
define( 'MIN_WP_VERSION', '4.7' );
define( 'MIN_PHP_VERSION', '5.6.0' );
define( 'WPDISCOURSE_VERSION', '2.1.9' );

require_once WPDISCOURSE_PATH . 'lib/plugin-utilities.php';
require_once WPDISCOURSE_PATH . 'lib/template-functions.php';
require_once WPDISCOURSE_PATH . 'lib/utilities.php';
require_once WPDISCOURSE_PATH . 'lib/discourse.php';
require_once WPDISCOURSE_PATH . 'lib/discourse-comment.php';
require_once WPDISCOURSE_PATH . 'lib/discourse-publish.php';
require_once WPDISCOURSE_PATH . 'lib/sso-provider/sso.php';
require_once WPDISCOURSE_PATH . 'lib/sso-provider/discourse-sso.php';
require_once WPDISCOURSE_PATH . 'lib/webhook.php';
require_once WPDISCOURSE_PATH . 'lib/sync-discourse-user.php';
require_once WPDISCOURSE_PATH . 'lib/sync-discourse-topic.php';
require_once WPDISCOURSE_PATH . 'lib/email-notification.php';
require_once WPDISCOURSE_PATH . 'lib/sso-client/sso-client-base.php';
require_once WPDISCOURSE_PATH . 'lib/wordpress-email-verification.php';
require_once WPDISCOURSE_PATH . 'lib/discourse-comment-formatter.php';
require_once WPDISCOURSE_PATH . 'lib/sso-client/nonce.php';
require_once WPDISCOURSE_PATH . 'lib/sso-client/client.php';
require_once WPDISCOURSE_PATH . 'lib/sso-client/query-redirect.php';
require_once WPDISCOURSE_PATH . 'lib/shortcodes/sso-client.php';
require_once WPDISCOURSE_PATH . 'templates/html-templates.php';
require_once WPDISCOURSE_PATH . 'admin/discourse-sidebar/discourse-sidebar.php';
require_once WPDISCOURSE_PATH . 'admin/admin.php';

new WPDiscourse\Discourse\Discourse();
$discourse_email_notification = new WPDiscourse\EmailNotification\EmailNotification();
$discourse_publish = new WPDiscourse\DiscoursePublish\DiscoursePublish( $discourse_email_notification );
new WPDiscourse\Admin\DiscourseSidebar( $discourse_publish );
$discourse_comment_formatter = new WPDiscourse\DiscourseCommentFormatter\DiscourseCommentFormatter();
new WPDiscourse\DiscourseComment\DiscourseComment( $discourse_comment_formatter );
new WPDiscourse\WordPressEmailVerification\WordPressEmailVerification( 'discourse_email_verification_key', 'discourse' );
new WPDiscourse\DiscourseSSO\DiscourseSSO();
new WPDiscourse\SyncDiscourseUser\SyncDiscourseUser();
new WPDiscourse\SyncDiscourseTopic\SyncDiscourseTopic();
new WPDiscourse\SSOClient\Client();
new WPDiscourse\SSOClient\QueryRedirect();
new WPDiscourse\SSOClient\SSOClientShortcode();
