<?php
/**
 * WPOA Logout class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_Logout' ) ) {

	/**
	 * Class WPOA_Logout
	 */
	class WPOA_Logout {

		/**
		 * WPOA_Logout constructor.
		 */
		public function __construct() {
			add_action( 'wp_logout', array( $this, 'end_logout' ) );
		}

		/**
		 * Ends the logout request by redirecting the user to the desired page.
		 *
		 * @return bool
		 */
		public function end_logout() {
			$_SESSION['WPOA']['RESULT'] = 'Logged out successfully.';

			if ( is_user_logged_in() ) {

				// user is logged in and trying to logout...get their Last Page.
				$last_url = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			} else {

				// user is NOT logged in and trying to logout...get their Last Page minus the querystring so we don't trigger the logout confirmation.
				$last_url = isset( $_SERVER['HTTP_REFERER'] ) ? strtok( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'], '?' ) ) ) : '';
			}

			unset( $_SESSION['WPOA']['LAST_URL'] );

			WPOA_::$login->clear_state();
			$redirect_method = get_option( 'wpoa_logout_redirect' );
			$redirect_url    = '';

			switch ( $redirect_method ) {
				case 'default_handling':
					return false;
				case 'home_page':
					$redirect_url = site_url();
					break;
				case 'last_page':
					$redirect_url = $last_url;
					break;
				case 'specific_page':
					$redirect_url = get_permalink( get_option( 'wpoa_logout_redirect_page' ) );
					break;
				case 'admin_dashboard':
					$redirect_url = admin_url();
					break;
				case 'user_profile':
					$redirect_url = get_edit_user_link();
					break;
				case 'custom_url':
					$redirect_url = get_option( 'wpoa_logout_redirect_url' );
					break;
			}

			wp_safe_redirect( $redirect_url );

			die();
		}
	}

	new WPOA_Logout();
}
