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
			echo do_shortcode( '[wpoa_unlink_list]' );
		}
	}

	new WPOA_User_Profile();
}
