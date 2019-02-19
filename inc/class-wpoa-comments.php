<?php
/**
 * WPOA Comments class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_Comments' ) ) {

	/**
	 * Class WPOA_Logout
	 */
	class WPOA_Comments {

		/**
		 * WPOA_Comments constructor.
		 */
		public function __construct() {
			add_filter( 'comment_form_defaults', array( $this, 'customize_comment_form_fields' ) );
		}

		/**
		 * Show a custom login form at the top of the default comment form.
		 *
		 * @param array $fields Fields.
		 *
		 * @return mixed
		 */
		public function customize_comment_form_fields( $fields ) {
			$html   = '';
			$design = get_option( 'wpoa_login_form_show_comments_section' );

			if ( 'None' !== $design ) {

				// TODO: we need to use $settings defaults here, not hard-coded defaults...
				$html                  .= WPOA::$login->login_form_content( $design, 'none', 'buttons-column', 'Connect with', 'center', 'conditional', 'conditional', 'Please login:', 'You are already logged in.', 'Logging in...', 'Logging out...' );
				$fields['logged_in_as'] = $html;
			}

			return $fields;
		}

		/**
		 * Show a custom login form at the top of the default comment form
		 */
		public function wpoa_customize_comment_form() {
			$html   = '';
			$design = get_option( 'wpoa_login_form_show_comments_section' );

			if ( 'None' !== $design ) {

				// TODO: we need to use $settings defaults here, not hard-coded defaults...
				$html .= WPOA::$login->login_form_content( $design, 'none', 'buttons-column', 'Connect with', 'center', 'conditional', 'conditional', 'Please login:', 'You are already logged in.', 'Logging in...', 'Logging out...' );
			}

			echo $html; // WPCS: XSS ok.
		}
	}

	new WPOA_Comments();
}
