<?php
/**
 * WPOA AJAX class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_AJAX' ) ) {

	/**
	 * Class WPOA_AJAX
	 */
	class WPOA_AJAX {

		/**
		 * WPOA_AJAX constructor.
		 */
		public function __construct() {
			add_action( 'wp_ajax_wpoa_logout', array( $this, 'logout_user' ) );
			add_action( 'wp_ajax_wpoa_unlink_account', array( $this, 'unlink_account' ) );
			add_action( 'wp_ajax_nopriv_wpoa_unlink_account', array( $this, 'unlink_account' ) );
		}

		/**
		 * Unlinks a third-party provider from an existing WordPress user account.
		 */
		public function unlink_account() {
			global $current_user;
			global $wpdb;

			if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'wpoa-unlink-nonce' ) ) {

				// get wpoa_identity row index that the user wishes to unlink.
				$wpoa_identity_row = isset( $_POST['wpoa_identity_row'] ) ? sanitize_text_field( wp_unslash( $_POST['wpoa_identity_row'] ) ) : '';
				$provider          = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';

				if ( '' !== $wpoa_identity_row ) {

					// Get the current user.
					wp_get_current_user();
					$user_id = $current_user->ID;

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$query_result = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE $wpdb->usermeta.user_id = %d AND $wpdb->usermeta.meta_key = 'wpoa_identity' AND $wpdb->usermeta.umeta_id = %d", $user_id, $wpoa_identity_row ) );
				}

				if ( $query_result ) {
					if ( '' !== $provider ) {
						if ( '1' === get_option( 'wpoa_email_notify_unlink' ) ) {
							$message = $current_user->user_login . ' unlinked ' . $provider . ' from their account.';

							$x = wp_mail( WPOA::$admin_email, '[' . get_option( 'blogname' )'] Unlinked Account', $message );
						}
					}

					echo wp_json_encode( array( 'result' => 1 ) );

					die();
				}
			}

			// Notify client of the failure.
			echo wp_json_encode( array( 'result' => 0 ) );

			die();
		}

		/**
		 * Logout the WordPress user.
		 *
		 * TODO: this is usually called from a custom logout button, but we could have the button call /wp-logout.php?action=logout for more consistency...
		 */
		public function logout_user() {

			// logout the user.
			$user = null;         // nullify the user.
			session_destroy();    // destroy the php user session.

			wp_logout();          // logout the WordPress user...this gets hooked and diverted to wpoa_end_logout() for final handling.
		}
	}

	new WPOA_AJAX();
}
