<?php
/**
 * WP-OAuth Facbook config
 *
 * @package WP-OAuth
 */

defined( 'ABSPATH' ) || exit;

// General singleton class.
require_once WPOA::$dir . 'inc/class-redux-oauth.php';

$oauth = Redux_oAuth::getInstance( $this );

$oauth->set_config(
	array(
		'scope'              => 'email',
		'url_auth'           => 'https://www.facebook.com/dialog/oauth?',
		'url_token'          => 'https://graph.facebook.com/oauth/access_token?',
		'url_user'           => 'https://graph.facebook.com/me?',
		'get_oauth_token'    => array(
			'access_token' => 'access_token',
			'expires_in'   => 'expires_in',
			'json_decode'  => true,
		),
		'get_oauth_identity' => array(
			'params' => array(
				'fields' => 'id,email,name,first_name,last_name,gender,picture,timezone',
			),
		),
		'get_oauth_identity' => array(
//			'id'         => 'id',
//			'email'      => 'email'
		),
		'provider'           => 'facebook',
		'code'               => 'code',
	)
);

$oauth->auth_flow();
