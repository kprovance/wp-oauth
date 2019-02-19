<?php
/**
 * WPOA Admin class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_Admin' ) ) {

	/**
	 * Class WPOA_Admin
	 */
	class WPOA_Admin {

		/**
		 * WPOA_Admin constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			add_filter( 'pre_update_option_wpoa_login_form_designs', array( $this, 'save_fix_json' ), 10, 2 );
		}

		/**
		 * Registers all settings that have been defined at the top of the plugin
		 */
		public function register_settings() {
			foreach ( WPOA::$settings as $setting_name => $default_value ) {
				register_setting( 'wpoa_settings', $setting_name );
			}
		}

		/**
		 * Add the main settings page:
		 */
		public function settings_page() {
			add_options_page(
				'WP-OAuth Options',
				'WP-OAuth',
				'manage_options',
				'WP-OAuth',
				array(
					$this,
					'settings_page_content',
				)
			);
		}

		/**
		 * Render the main settings page content:
		 */
		public function settings_page_content() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-oauth' ) );
			}

			$blog_url = rtrim( site_url(), '/' ) . '/';

			include WPOA::$dir . 'inc/admin/wp-oauth-settings.php';
		}

		/**
		 * Output the custom login form design selector
		 *
		 * @param string $id ID.
		 * @param bool   $master Master.
		 *
		 * @return string
		 */
		public static function login_form_designs_selector( $id = '', $master = false ) {
			$html = '';

			$designs_json  = get_option( 'wpoa_login_form_designs' );
			$designs_array = json_decode( $designs_json );

			$name  = str_replace( '-', '_', $id );
			$html .= '<select id="' . $id . '" name="' . $name . '">';

			if ( true === $master ) {
				foreach ( $designs_array as $key => $val ) {
					$html .= '<option value="">' . $key . '</option>';
				}

				$html .= '</select>';
				$html .= '<input type="hidden" id="wpoa-login-form-designs" name="wpoa_login_form_designs" value="' . rawurlencode( $designs_json ) . '">';
			} else {
				$html .= '<option value="None">None</option>';
				foreach ( $designs_array as $key => $val ) {
					$html .= '<option value="' . $key . '" ' . selected( get_option( $name ), $key, false ) . '>' . $key . '</option>';
				}
				$html .= '</select>';
			}

			return $html;
		}

		/**
		 * Convert encodedurl back to JSON for database save.
		 *
		 * @param mixed $new_value New value.
		 * @param mixed $old_value Old value.
		 *
		 * @return string
		 */
		public function save_fix_json( $new_value, $old_value ) {
			$new_value = rawurldecode( $new_value );

			return $new_value;
		}
	}

	new WPOA_Admin();
}
