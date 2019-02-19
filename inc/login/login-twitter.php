<?php

require_once WPOA::$dir . 'inc/hybridauth/Hybrid/Auth.php';

// General singleton class.
require_once WPOA::$dir . 'inc/class-redux-oauth.php';

$oauth = Redux_oAuth::getInstance( $this );

$config = array(
	"base_url" => $oauth->config['redirect_uri'],
	"providers" => array(
		//"Facebook" => array(
		//	"enabled" => true,
		//	"keys" => array("id" => $oauth->config['client_id'] , "secret" => $oauth->config['client_secret']),
		//	"trustForwarded" => false,
		//),
		"Twitter" => array(
			"enabled" => true,
			
			"includeEmail" => true,
		),
	),
	// If you want to enable logging, set 'debug_mode' to true.
	// You can also set it to
	// - "error" To log only error messages. Useful in production
	// - "info" To log info and error messages (ignore debug messages)
	"debug_mode" => false,
	// Path to file writable by the web server. Required if 'debug_mode' is not false
	"debug_file" => "",
);


try{
	$hybridauth = new Hybrid_Auth( $config );

	$twitter = $hybridauth->authenticate( "Twitter" );

	$user_profile = $twitter->getUserProfile();
	print_r($user_profile);

	exit();

	$user_contacts = $twitter->getUserContacts();
}
catch( Exception $e ){
	WPOA::$login->end_login( 'Ooophs, we got an error: '.$e->getMessage() );
}