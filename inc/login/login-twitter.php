<?php
/**
 * WP-OAuth Twitter config
 *
 * @package WP-OAuth
 */

defined( 'ABSPATH' ) || exit;

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