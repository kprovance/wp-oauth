<?php
/**
 * Plugin Name: WP-OAuth
 * Plugin URI: http://github.com/perrybutler/wp-oauth
 * Description: A WordPress plugin that allows users to login or register by authenticating with an existing Google, Facebook, LinkedIn, Github, Reddit or Windows Live account via OAuth 2.0. Easily drops into new or existing sites, integrates with existing users.
 * Version: 0.4.1
 * Author: Perry Butler
 * Author URI: http://glassocean.net
 * License: GPL2
 *
 * @package         Redux OAuth
 * @author          Team Redux (Dovy Paukstys <dovy@reduxframework.com> and Kevin Provance <kevin@reduxframework.com>)
 * @license         GNU General Public License, version 3
 * @copyright       2019 Redux.io
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// start the user session for persisting user/login state during ajax, header redirect, and cross domain calls.
if ( ! isset( $_SESSION ) ) {
	session_start();
}

// Require the main plugin class.
require_once plugin_dir_path( __FILE__ ) . 'class-wpoa.php';

WPOA::$version = '0.4.1';

// Register hooks that are fired when the plugin is activated and deactivated, respectively.
register_activation_hook( __FILE__, array( 'WPOA', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPOA', 'deactivate' ) );

WPOA::instance();
