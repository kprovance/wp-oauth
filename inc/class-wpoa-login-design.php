<?php
/**
 * WPOA Login Design class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_Login_Design' ) ) {

	/**
	 * Class WPOA_Login_Design
	 */
	class WPOA_Login_Design {

		/**
		 * WPOA_Login_Design constructor.
		 */
		public function __construct() {
			// restore default settings if necessary; this might get toggled by the admin or forced by a new version of the plugin.
			if ( get_option( 'wpoa_logo_links_to_site' ) === '1' ) {
				add_filter( 'login_headerurl', array( $this, 'logo_link' ) );
			}

			add_filter( 'login_message', array( $this, 'customize_login_screen' ) );
		}

		/**
		 * Show a custom login form on the default login screen:
		 */
		public function customize_login_screen() {
			$html   = '';
			$design = get_option( 'wpoa_login_form_show_login_screen' );

			if ( 'None' !== $design ) {
				// TODO: we need to use $settings defaults here, not hard-coded defaults...
				$html .= WPOA::$login->login_form_content( $design, 'none', 'buttons-column', 'Connect with', 'center', 'conditional', 'conditional', 'Please login:', 'You are already logged in.', 'Logging in...', 'Logging out...' );
			}
			echo $html; // WPCS: XSS ok.
		}

		/**
		 * Force the login screen logo to point to the site instead of wordpress.org:
		 *
		 * @return string|void
		 */
		public function logo_link() {
			return get_bloginfo( 'url' );
		}

	}

	new WPOA_Login_Design();
}
