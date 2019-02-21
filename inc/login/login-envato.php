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
function envato_get_oauth_identity( $e ) {
	$params['access_token'] = $_SESSION['WPOA']['ACCESS_TOKEN'];
	$url = $e->config['url_user'];
	$oauth_identity = array();

	$result_obj = 'curl' === $e->config['http_util'] ? $e->curl( $params, $url ) : $e->stream( $params, $url );
	$result_obj = json_decode( $result_obj, true );
	$oauth_identity['name'] = $result_obj['account']['firstname'] . " " . $result_obj['account']['surname'];
	$oauth_identity['image'] = $result_obj['account']['image'];
	$oauth_identity['country'] = $result_obj['account']['country'];

	$url = "https://api.envato.com/v1/market/private/user/username.json";
	$result_obj = 'curl' === $e->config['http_util'] ? $e->curl( $params, $url ) : $e->stream( $params, $url );
	$result_obj = json_decode( $result_obj, true );
	$oauth_identity['id'] = $result_obj['username'];

	$url = "https://api.envato.com/v1/market/private/user/email.json";
	$result_obj = 'curl' === $e->config['http_util'] ? $e->curl( $params, $url ) : $e->stream( $params, $url );
	$result_obj = json_decode( $result_obj, true );
	$oauth_identity['email'] = $result_obj['email'];

	$oauth_identity['provider'] = $_SESSION['WPOA']['PROVIDER'];

	if ( ! $oauth_identity['id'] ) {
		WPOA::$login->end_login( 'Sorry, we couldn\'t log you in. User identity was not found. Please notify the admin or try again later.' );
	}
	WPOA::$login->login_user( $oauth_identity );
}
add_action('WPOA_envato_get_oauth_identity', 'envato_get_oauth_identity');
