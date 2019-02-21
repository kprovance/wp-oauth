<?php
/**
 * WPOA Shortcodes class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_Shortcodes' ) ) {

	/**
	 * Class WPOA_Shortcodes
	 */
	class WPOA_Shortcodes {

		/**
		 * WPOA_Shortcodes constructor.
		 */
		public function __construct() {
			add_shortcode( 'wpoa_login_form', array( $this, 'login_form' ) );
			add_shortcode( 'wpoa_unlink_list', array( $this, 'unlink_list' ) );
		}

		/**
		 * Shortcode which display an unlink account list.
		 *
		 * @param array $atts Attributes.
		 *
		 * @return string
		 */
		public function unlink_list( $atts ) {
			$a = shortcode_atts(
				array(),
				$atts
			);

			global $current_user;
			global $wpdb;

			// Get the current user.
			wp_get_current_user();
			$user_id = $current_user->ID;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$query_result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->usermeta WHERE %d = $wpdb->usermeta.user_id AND $wpdb->usermeta.meta_key = 'wpoa_identity'", $user_id ) );

			// List the wpoa_identity records.
			$html  = '<div id="wpoa-linked-accounts">';
			$html .= '<h3>Linked Accounts</h3>';
			$html .= '<p>Manage the linked accounts which you have previously authorized to be used for logging into this website.</p>';
			$html .= '<table class="form-table">';
			$html .= '<tr valign="top">';
			$html .= '<th scope="row">Your Linked Providers</th>';
			$html .= '<td>';

			if ( 0 === count( $query_result ) ) {
				$html .= "<p>You currently don't have any accounts linked.</p>";
			}

			$html .= "<div class='wpoa-linked-accounts'>";

			foreach ( $query_result as $wpoa_row ) {
				$wpoa_identity_parts = explode( '|', $wpoa_row->meta_value );
				$oauth_provider      = $wpoa_identity_parts[0];
				$oauth_id            = $wpoa_identity_parts[1]; // keep this private, don't send to client.
				$time_linked         = $wpoa_identity_parts[2];
				$linked_email        = isset( $wpoa_identity_parts[3] ) ? $wpoa_identity_parts[3] : '';
				$linked_name         = isset( $wpoa_identity_parts[4] ) ? ' (' . $wpoa_identity_parts[4] . ')' : '';
				$local_time          = strtotime( '-' . sanitize_text_field( wp_unslash( $_COOKIE['gmtoffset'] ) ) . ' hours', $time_linked ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$nonce               = wp_create_nonce( 'wpoa-unlink-nonce' );

				$html .= '<div>' . esc_html( $oauth_provider ) . ' as ' . esc_html( $linked_email ) . esc_html( $linked_name ) . ' on ' . esc_html( date( 'F d, Y h:i A', $local_time ) ) . ' <a class="wpoa-unlink-account" data-nonce="' . esc_attr( $nonce ) . '" data-wpoa-identity-row="' . esc_attr( $wpoa_row->umeta_id ) . '" href="#">Unlink</a></div>';
			}

			$html .= '</div>';
			$html .= '</td>';
			$html .= '</tr>';
			$html .= '<tr valign="top">';
			$html .= '<th scope="row">Link Another Provider</th>';
			$html .= '<td>';

			$design = get_option( 'wpoa_login_form_show_profile_page' );

			if ( 'None' !== $design ) {
				// TODO: we need to use $settings defaults here, not hard-coded defaults...
				$html .= WPOA::$login->login_form_content( $design, 'none', 'buttons-row', 'Link', 'left', 'always', 'never', 'Select a provider:', 'Select a provider:', 'Authenticating...', '' ); // WPCS: XSS ok.
			}

			$html .= '</div>';
			$html .= '</td>';
			$html .= '</td>';
			$html .= '</table>';

			return $html;
		}

		/**
		 * Shortcode which allows adding the wpoa login form to any post or page.
		 *
		 * @param array $atts Attributes.
		 *
		 * @return string
		 */
		public function login_form( $atts ) {
			$a = shortcode_atts(
				array(
					'design'            => '',
					'icon_set'          => 'none',
					'button_prefix'     => '',
					'layout'            => 'links-column',
					'align'             => 'left',
					'show_login'        => 'conditional',
					'show_logout'       => 'conditional',
					'logged_out_title'  => 'Please login:',
					'logged_in_title'   => 'You are already logged in.',
					'logging_in_title'  => 'Logging in...',
					'logging_out_title' => 'Logging out...',
					'style'             => '',
					'class'             => '',
				),
				$atts
			);

			// Convert attribute strings to proper data types.
			// $a['show_login'] = filter_var($a['show_login'], FILTER_VALIDATE_BOOLEAN);
			// $a['show_logout'] = filter_var($a['show_logout'], FILTER_VALIDATE_BOOLEAN);.
			// Get the shortcode content.
			$html = WPOA::$login->login_form_content( $a['design'], $a['icon_set'], $a['layout'], $a['button_prefix'], $a['align'], $a['show_login'], $a['show_logout'], $a['logged_out_title'], $a['logged_in_title'], $a['logging_in_title'], $a['logging_out_title'], $a['style'], $a['class'] );

			return $html;
		}
	}

	new WPOA_Shortcodes();
}
