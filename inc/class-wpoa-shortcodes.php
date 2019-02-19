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
			add_shortcode( 'wpoa_login_form', array( $this, 'wpoa_login_form' ) );
		}

		/**
		 * Shortcode which allows adding the wpoa login form to any post or page:
		 *
		 * @param array $atts Attributes.
		 *
		 * @return string
		 */
		public function wpoa_login_form( $atts ) {
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
			$html = WPOA_::$login->login_form_content( $a['design'], $a['icon_set'], $a['layout'], $a['button_prefix'], $a['align'], $a['show_login'], $a['show_logout'], $a['logged_out_title'], $a['logged_in_title'], $a['logging_in_title'], $a['logging_out_title'], $a['style'], $a['class'] );

			return $html;
		}
	}

	new WPOA_Shortcodes();
}
