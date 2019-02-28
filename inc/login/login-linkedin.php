<?php
/**
 * WP-OAuth LinkedIn config
 *
 * @package WP-OAuth
 */

defined( 'ABSPATH' ) || exit;

$oauth = Redux_oAuth::instance( $this );

$oauth->set_config(
	array(
		'provider'           => 'LinkedIn',
		'code'               => 'code',
		'url_auth'           => 'https://www.linkedin.com/uas/oauth2/authorization?',
		'url_token'          => 'https://www.linkedin.com/uas/oauth2/accessToken?',
		'url_user'           => 'https://api.linkedin.com/v1/people/~:(id,first-name,last-name,email-address)?',
		'scope'              => 'r_basicprofile r_emailaddress',
		'get_oauth_token'    => array(
			'access_token'     => 'access_token',
			'json_decode'      => true,
			'params_as_string' => true,
		),
		'get_oauth_identity' => array(
			'access_token' => 'oauth2_access_token',
			'header'       => 'x-li-format: json',
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
function linkedin_fix_oauth_identity( $oauth_identity ) {
	$temp = array();
	$temp = $oauth_identity;

	$temp['id']    = $oauth_identity['id'];
	$temp['email'] = $oauth_identity['emailAddress'];
	$temp['name']  = $oauth_identity['firstName'] . ' ' . $oauth_identity['lastName'];

	return $temp;
}

add_filter( 'WPOA_linkedin_fix_oauth_identity', 'linkedin_fix_oauth_identity' );

$oauth->auth_flow();
