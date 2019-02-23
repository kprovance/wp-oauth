<?php
/**
 * WP-OAuth Github config
 *
 * @package WP-OAuth
 */

defined( 'ABSPATH' ) || exit;

$oauth = Redux_oAuth::instance( $this );

$oauth->set_config(
	array(
		'provider'        => 'Github',
		'code'            => 'code',
		'url_auth'        => 'https://github.com/login/oauth/authorize?',
		'url_token'       => 'https://github.com/login/oauth/access_token?',
		'url_user'        => 'https://api.github.com/user?',
		'scope'           => 'user:email',
		'get_oauth_token' => array(
			'access_token' => 'access_token',
			'json_decode'  => false,
			'parse_str'    => true,
		),
	)
);

$oauth->auth_flow();
