<?php
/**
 * WP-OAuth Slack config
 *
 * @package WP-OAuth
 */

defined( 'ABSPATH' ) || exit;

$oauth = Redux_oAuth::instance( $this );

$oauth->set_config(
	array(
		'provider'           => 'Slack',
		'code'               => 'code',
		'url_auth'           => 'https://slack.com/oauth/authorize?',
		'url_token'          => 'https://slack.com/api/oauth.access?',
		'url_user'           => 'https://slack.com/api/users.identity?',
		'scope'              => 'identity.basic, identity.email',
		'get_oauth_token'    => array(
			'access_token'     => 'access_token',
			'json_decode'      => true,
			'params_as_string' => true,
		),
		'get_oauth_identity' => array(
			'access_token' => 'token',
		),
	)
);

function slack_fix_oauth_identity( $oauth_identity ) {
	$temp = array();
	$temp = $oauth_identity;

	$temp['id'] = $oauth_identity['user']['id'];
	$temp['email'] = $oauth_identity['user']['email'];
	$temp['name'] = $oauth_identity['user']['name'];

	return $temp;
}

add_filter( 'WPOA_Slack_fix_oauth_identity', 'slack_fix_oauth_identity' );

$oauth->auth_flow();
