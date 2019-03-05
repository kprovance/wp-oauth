<?php
/**
 * WPOA Messages class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_Messages' ) ) {

	/**
	 * Class WPOA_Messages
	 */
	class WPOA_Messages {

		/**
		 * WPOA_Messages constructor.
		 */
		public function __construct() {

			// push login messages into the DOM if the setting is enabled.
			if ( get_option( 'wpoa_show_login_messages' ) !== false ) {
				add_action( 'wp_footer', array( $this, 'push_login_messages' ) );
				add_filter( 'admin_footer', array( $this, 'push_login_messages' ) );
				add_filter( 'login_footer', array( $this, 'push_login_messages' ) );
			}
		}

		/**
		 * Pushes login messages into the dom where they can be extracted by javascript
		 */
		public function push_login_messages() {
			if ( isset( $_SESSION['WPOA'] ) ) {
				$result                     = $_SESSION['WPOA']['RESULT'];
				$_SESSION['WPOA']['RESULT'] = '';

				echo '<div id="wpoa-result">' . esc_html( $result ) . '</div>';
			}
		}
	}

	new WPOA_Messages();
}
