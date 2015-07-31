<?php
/*
Plugin Name: WP-Discourse
Description: Use Discourse as a community engine for your WordPress blog
Version: 0.6.6
Author: Sam Saffron, Robin Ward
Author URI: https://github.com/discourse/wp-discourse
Plugin URI: https://github.com/discourse/wp-discourse
GitHub Plugin URI: https://github.com/discourse/wp-discourse
*/
/*  Copyright 2014 Civilized Discourse Construction Kit, Inc (team@discourse.org)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

define( 'WPDISCOURSE_PATH', plugin_dir_path( __FILE__ ) );

require_once( __DIR__ . '/lib/discourse.php' );
require_once( __DIR__ . '/lib/admin.php' );
require_once( __DIR__ . '/lib/sso.php' );

$discourse = new Discourse();
$discourse_admin = new DiscourseAdmin();

register_activation_hook( __FILE__, array( $discourse, 'install' ) );
