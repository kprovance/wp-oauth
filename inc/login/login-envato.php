<?php
/**
 * WP-OAuth Facbook config
 *
 * @package WP-OAuth
 */

defined( 'ABSPATH' ) || exit;

// General singleton class.
require_once WPOA::$dir . 'inc/class-redux-oauth.php';

$oauth = Redux_oAuth::instance( $this );

$oauth->set_config(
	array(
		'url_auth'           => 'https://api.envato.com/authorization?',
		'url_token'          => 'https://api.envato.com/token?',
		'url_user'           => 'https://api.envato.com/v1/market/private/user/account.json',
		'get_oauth_token'    => array(
			'access_token' => 'access_token',
			'expires_in'   => 'expires_in',
			'json_decode'  => true,
			'params_as_string'=> true,
		),
		'get_oauth_identity'   => array(),
		'provider'             => 'envato',
		'code'                 => 'code',
		'authorization_header' => 'Bearer'
	)
);

// We need extra calls to construct the identitity
function envato_login($e, $oauth_identity) {
	print_r($oauth_identity);
	echo "did it!";
	exit();
}
add_action('WPOA_envato_login', 'envato_login');

$oauth->auth_flow();

