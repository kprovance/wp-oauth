<?php
/**
 * WPOA Register
 *
 * @package     WP-OAuth
 * @since       1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $wpdb;

// initiate the user session.
session_start();

// Prevent users from registering if the option is turned off in the dashboard.
if ( ! get_option( 'users_can_register' ) ) {
	$_SESSION['WPOA']['RESULT'] = 'Sorry, user registration is disabled at this time. Your account could not be registered. Please notify the admin or try again later.';
	header( 'Location: ' . $_SESSION['WPOA']['LAST_URL'] );
	exit;
}

// Registration was initiated from an oauth provider, set the username and password automatically.
if ( '' !== $_SESSION['WPOA']['USER_ID'] ) {
	$username = $oauth_identity['email'];

	if ( '' === $username ) {
		$username = str_replace( ' ', '', $oauth_identity['name'] );
	}

	if ( '' === $username ) {
		$username = $oauth_identity['provider'] . '_User_' . uniqid( 'redux' );
	}

	$password = wp_generate_password();
}

// Registration was initiated from the standard sign up form, set the username and password that was requested by the user.
if ( '' === $_SESSION['WPOA']['USER_ID'] ) {

	// this registration was initiated from the standard Registration page, create account and login the user automatically.
	if ( isset( $_POST['identity'] ) ) {
		$username = sanitize_text_field( wp_unslash( $_POST['identity'] ) ); // WPCS: CSRF ok.
	}

	if ( isset( $_POST['password'] ) ) {
		$password = sanitize_text_field( wp_unslash( $_POST['password'] ) ); // WPCS: CSRF ok.
	}
}

// Now attempt to generate the user and get the user id.
$user_id = wp_create_user( $username, $password, $username ); // We use wp_create_user instead of wp_insert_user so we can handle the error when the user being registered already exists.

// Check if the user was actually created.
if ( is_wp_error( $user_id ) ) {

	// There was an error during registration, redirect and notify the user.
	$_SESSION['WPOA']['RESULT'] = $user_id->get_error_message();
	header( 'Location: ' . $_SESSION['WPOA']['LAST_URL'] );

	exit;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$update_username_result = $wpdb->update(
	$wpdb->users,
	array(
		'user_login'    => $username,
		'user_nicename' => $username,
		'display_name'  => isset( $oauth_identity['name'] ) ? $oauth_identity['name'] : $username,
	),
	array( 'ID' => $user_id )
);

$update_nickname_result = update_user_meta( $user_id, 'nickname', isset( $oauth_identity['name'] ) ? $oauth_identity['name'] : $username );

// Apply the custom default user role.
$user_role          = get_option( 'wpoa_new_user_role' );
$update_role_result = wp_update_user(
	array(
		'ID'   => $user_id,
		'role' => $user_role,
	)
);

// Proceed if no errors were detected.
if ( false === $update_username_result ) { // || false === $update_nickname_result ) {
	// There was an error during registration, redirect and notify the user.
	$_SESSION['WPOA']['RESULT'] = 'Could not rename the username during registration. Please contact an admin or try again later.';
	header( 'Location: ' . $_SESSION['WPOA']['LAST_URL'] );

	exit;
} elseif ( false === $update_role_result ) {

	// There was an error during registration, redirect and notify the user.
	$_SESSION['WPOA']['RESULT'] = 'Could not assign default user role during registration. Please contact an admin or try again later.';
	header( 'Location: ' . $_SESSION['WPOA']['LAST_URL'] );

	exit;
} else {

	// Registration was successful, the user account was created, proceed to login the user automatically...
	// Associate the WordPress user account with the now-authenticated third party account.
	WPOA::$login->link_account( $user_id, $oauth_identity );

	if ( '1' === get_option( 'wpoa_email_notify_link' ) ) {
		$message = $current_user->user_login . ' created an account using their ' . $_SESSION['WPOA']['PROVIDER'] . ' account.' . "\n\n" . 'Username: ' . $oauth_identity['name'] . "\n\n" . 'EMail: ' . $oauth_identity['email'];

		$x = wp_mail( WPOA::$admin_email, '[Redux.io] New Account', $message );
	}

	// Attempt to login the new user (this could be error prone).
	$creds                  = array();
	$creds['user_login']    = $username;
	$creds['user_password'] = $password;
	$creds['remember']      = true;
	$user                   = wp_signon( $creds, false );

	// Send a notification e-mail to the admin and the new user (we can also build our own email if necessary).
	if ( ! get_option( 'wpoa_suppress_welcome_email' ) ) {
		// phpcs:ignore Squiz.PHP.CommentedOutCode
		// wp_mail($username, "New User Registration", "Thank you for registering!\r\nYour username: " . $username . "\r\nYour password: " . $password, $headers);.
		wp_new_user_notification( $user_id );
	}

	// Finally redirect the user back to the page they were on and notify them of successful registration.
	WPOA::$login->end_login( 'You have been registered successfully!' );

	exit;
}
