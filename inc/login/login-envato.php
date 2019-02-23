<?php
/**
 * WP-OAuth Envato config
 *
 * @package WP-OAuth
 */

defined( 'ABSPATH' ) || exit;

// General singleton class.
require_once WPOA::$dir . 'inc/class-redux-oauth.php';

$oauth = Redux_oAuth::instance( $this );

$oauth->set_config(
	array(
		'url_auth'             => 'https://api.envato.com/authorization?',
		'url_token'            => 'https://api.envato.com/token?',
		'url_user'             => 'https://api.envato.com/v1/market/private/user/account.json',
		'get_oauth_token'      => array(
			'access_token'     => 'access_token',
			'expires_in'       => 'expires_in',
			'json_decode'      => true,
			'params_as_string' => true,
		),
		'get_oauth_identity'   => array(),
		'provider'             => 'envato',
		'code'                 => 'code',
		'authorization_header' => 'Bearer',
	)
);



/**
 * We need extra calls to construct the identitity.
 *
 * @param Object $e object.
 *
 * @return array
 */
function envato_get_oauth_identity( $e ) {
	$params['access_token'] = $_SESSION['WPOA']['ACCESS_TOKEN'];
	$url                    = $e->config['url_user'];
	$oauth_identity         = array();

	// Fetch the profile.
	$result_obj = $e->remote_post( $params, $url );
	$result_obj = json_decode( $result_obj, true );

	$oauth_identity['name']    = $result_obj['account']['firstname'] . ' ' . $result_obj['account']['surname'];
	$oauth_identity['image']   = $result_obj['account']['image'];
	$oauth_identity['country'] = $result_obj['account']['country'];

	// Fetch the username.
	$url                  = 'https://api.envato.com/v1/market/private/user/username.json';
	$result_obj           = $e->remote_post( $params, $url );
	$result_obj           = json_decode( $result_obj, true );
	$oauth_identity['id'] = $result_obj['username'];

	// Fetch the email.
	$url                     = 'https://api.envato.com/v1/market/private/user/email.json';
	$result_obj              = $e->remote_post( $params, $url );
	$result_obj              = json_decode( $result_obj, true );
	$oauth_identity['email'] = $result_obj['email'];

	$oauth_identity['provider'] = $_SESSION['WPOA']['PROVIDER'];

	return $oauth_identity;
}

add_filter( 'WPOA_envato_get_oauth_identity', 'envato_get_oauth_identity' );

$oauth->auth_flow();
