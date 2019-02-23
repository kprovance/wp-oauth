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

$oauth->auth_flow();
