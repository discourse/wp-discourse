<?php
/**
 * Plugin Name: WP-Discourse
 * Description: Use Discourse as a community engine for your WordPress blog
 * Version: 1.7.6
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
define( 'MIN_PHP_VERSION', '5.4.0' );
define( 'WPDISCOURSE_VERSION', '1.7.6' );

require_once __DIR__ . '/lib/plugin-utilities.php';
require_once __DIR__ . '/lib/template-functions.php';
require_once __DIR__ . '/lib/utilities.php';
require_once __DIR__ . '/lib/discourse.php';
require_once __DIR__ . '/lib/discourse-comment.php';
require_once __DIR__ . '/lib/discourse-publish.php';
require_once __DIR__ . '/lib/sso-provider/sso.php';
require_once __DIR__ . '/lib/sso-provider/discourse-sso.php';
require_once __DIR__ . '/lib/webhook.php';
require_once __DIR__ . '/lib/discourse-user.php';
require_once __DIR__ . '/lib/discourse-webhook-refresh.php';
require_once __DIR__ . '/lib/email-notification.php';
require_once __DIR__ . '/lib/sso-client/sso-client-base.php';
require_once __DIR__ . '/lib/wordpress-email-verification.php';
require_once __DIR__ . '/lib/discourse-comment-formatter.php';
require_once __DIR__ . '/lib/sso-client/nonce.php';
require_once __DIR__ . '/lib/sso-client/client.php';
require_once __DIR__ . '/lib/sso-client/query-redirect.php';
require_once __DIR__ . '/lib/shortcodes/sso-client.php';
require_once __DIR__ . '/templates/html-templates.php';
require_once __DIR__ . '/admin/admin.php';

new WPDiscourse\Discourse\Discourse();
$discourse_email_notification = new WPDiscourse\EmailNotification\EmailNotification();
new WPDiscourse\DiscoursePublish\DiscoursePublish( $discourse_email_notification );
$discourse_comment_formatter = new WPDiscourse\DiscourseCommentFormatter\DiscourseCommentFormatter();
new WPDiscourse\DiscourseComment\DiscourseComment( $discourse_comment_formatter );
new WPDiscourse\WordPressEmailVerification\WordPressEmailVerification( 'discourse_email_verification_key', 'discourse' );
new WPDiscourse\DiscourseSSO\DiscourseSSO();
new WPDiscourse\DiscourseUser\DiscourseUser();
new WPDiscourse\DiscourseWebhookRefresh\DiscourseWebhookRefresh();
new WPDiscourse\SSOClient\Client();
new WPDiscourse\SSOClient\QueryRedirect();
new WPDiscourse\SSOClient\SSOClientShortcode();
