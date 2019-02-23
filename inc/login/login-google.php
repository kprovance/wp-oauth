<?php
/**
 * WP-OAuth Google config
 *
 * @package WP-OAuth
 */

defined( 'ABSPATH' ) || exit;

$oauth = Redux_oAuth::instance( $this );

$oauth->set_config(
	array(
		'provider'        => 'Google',
		'code'            => 'code',
		'url_auth'        => 'https://accounts.google.com/o/oauth2/auth?',
		'url_token'       => 'https://accounts.google.com/o/oauth2/token?',
		'url_user'        => 'https://www.googleapis.com/plus/v1/people/me?',
		'scope'           => 'email',
		'get_oauth_token' => array(
			'access_token' => 'access_token',
			'expires_in'   => 'expires_in',
			'json_decode'  => true,
		),
	)
);

/**
 * Make ouath_identity uniform.
 *
 * @param array $oauth_identity OAuth.
 *
 * @return array
 */
function google_fix_oauth_identity( $oauth_identity ) {
	$temp = array();
	$temp = $oauth_identity;

	$temp['email'] = isset( $oauth_identity['emails'][0]['value'] ) ? $oauth_identity['emails'][0]['value'] : '';
	$temp['name']  = isset( $oauth_identity['displayName'] ) ? $oauth_identity['displayName'] : '';
	echo 'callback';

	return $temp;
}

add_filter( 'WPOA_google_fix_oauth_identity', 'google_fix_oauth_identity' );

$oauth->auth_flow();
