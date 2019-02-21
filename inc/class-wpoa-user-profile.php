<?php
/**
 * WPOA User Profile class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_User_Profile' ) ) {

	/**
	 * Class WPOA_User_Profile
	 */
	class WPOA_User_Profile {

		/**
		 * WPOA_User_Profile constructor.
		 */
		public function __construct() {
			add_action( 'show_user_profile', array( $this, 'linked_accounts' ) );
		}

		/**
		 * Shows the user's linked providers, used on the 'Your Profile' page:
		 */
		public function linked_accounts() {
			global $current_user;
			global $wpdb;

			// Get the current user.
			wp_get_current_user();
			$user_id = $current_user->ID;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$query_result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->usermeta WHERE %d = $wpdb->usermeta.user_id AND $wpdb->usermeta.meta_key = 'wpoa_identity'", $user_id ) );

			// List the wpoa_identity records.
			echo '<div id="wpoa-linked-accounts">';
			echo '<h3>Linked Accounts</h3>';
			echo '<p>Manage the linked accounts which you have previously authorized to be used for logging into this website.</p>';
			echo '<table class="form-table">';
			echo '<tr valign="top">';
			echo '<th scope="row">Your Linked Providers</th>';
			echo '<td>';

			if ( 0 === count( $query_result ) ) {
				echo "<p>You currently don't have any accounts linked.</p>";
			}

			echo "<div class='wpoa-linked-accounts'>";

			foreach ( $query_result as $wpoa_row ) {
				$wpoa_identity_parts = explode( '|', $wpoa_row->meta_value );
				$oauth_provider      = $wpoa_identity_parts[0];
				$oauth_id            = $wpoa_identity_parts[1]; // keep this private, don't send to client.
				$time_linked         = $wpoa_identity_parts[2];
				$linked_email        = isset( $wpoa_identity_parts[3] ) ? $wpoa_identity_parts[3] : '';
				$linked_name         = isset( $wpoa_identity_parts[4] ) ? ' (' . $wpoa_identity_parts[4] . ')' : '';
				$local_time          = strtotime( '-' . sanitize_text_field( wp_unslash( $_COOKIE['gmtoffset'] ) ) . ' hours', $time_linked ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$nonce               = wp_create_nonce( 'wpoa-unlink-nonce' );

				echo '<div>' . esc_html( $oauth_provider ) . ' as ' . esc_html( $linked_email ) . esc_html( $linked_name ) . ' on ' . esc_html( date( 'F d, Y h:i A', $local_time ) ) . ' <a class="wpoa-unlink-account" data-nonce="' . esc_attr( $nonce ) . '" data-wpoa-identity-row="' . esc_attr( $wpoa_row->umeta_id ) . '" href="#">Unlink</a></div>';
			}

			echo '</div>';
			echo '</td>';
			echo '</tr>';
			echo '<tr valign="top">';
			echo '<th scope="row">Link Another Provider</th>';
			echo '<td>';

			$design = get_option( 'wpoa_login_form_show_profile_page' );

			if ( 'None' !== $design ) {
				// TODO: we need to use $settings defaults here, not hard-coded defaults...
				echo WPOA::$login->login_form_content( $design, 'none', 'buttons-row', 'Link', 'left', 'always', 'never', 'Select a provider:', 'Select a provider:', 'Authenticating...', '' ); // WPCS: XSS ok.
			}

			echo '</div>';
			echo '</td>';
			echo '</td>';
			echo '</table>';
		}
	}

	new WPOA_User_Profile();
}
