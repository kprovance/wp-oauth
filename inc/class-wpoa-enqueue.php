<?php
/**
 * WPOA Enqueue class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_Enqueue' ) ) {

	/**
	 * Class WPOA_Enqueue
	 */
	class WPOA_Enqueue {

		/**
		 * WPOA_Enqueue constructor.
		 */
		public function __construct() {
			// hook scripts and styles for frontend pages.
			add_action( 'wp_enqueue_scripts', array( $this, 'wpoa_init_frontend_scripts_styles' ) );

			// hook scripts and styles for backend pages.
			add_action( 'admin_enqueue_scripts', array( $this, 'wpoa_init_backend_scripts_styles' ) );

			// hook scripts and styles for login page.
			add_action( 'login_enqueue_scripts', array( $this, 'wpoa_init_login_scripts_styles' ) );
		}

		/**
		 * Init scripts and styles for use on FRONTEND PAGES:
		 */
		public function wpoa_init_frontend_scripts_styles() {

			// here we "localize" php variables, making them available as a js variable in the browser.
			$wpoa_cvars = array(
				// basic info.
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'template_directory'    => get_bloginfo( 'template_directory' ),
				'stylesheet_directory'  => get_bloginfo( 'stylesheet_directory' ),
				'plugins_url'           => plugins_url(),
				'plugin_dir_url'        => WPOA::$url,
				'url'                   => get_bloginfo( 'url' ),
				'logout_url'            => wp_logout_url(),
				// other.
				'show_login_messages'   => get_option( 'wpoa_show_login_messages' ),
				'logout_inactive_users' => get_option( 'wpoa_logout_inactive_users' ),
				'logged_in'             => is_user_logged_in(),
			);

			// load the core plugin scripts/styles.
			wp_enqueue_script(
				'wpoa-script',
				WPOA::$url . 'assets/js/wp-oauth.js',
				array( 'jquery' ),
				WPOA::$version,
				true
			);

			wp_localize_script(
				'wpoa-script',
				'wpoa_cvars',
				$wpoa_cvars
			);

			wp_enqueue_style(
				'wpoa-style',
				WPOA::$url . 'assets/css/wp-oauth.css',
				array(),
				WPOA::$version,
				'all'
			);
		}

		/**
		 * Init scripts and styles for use on BACKEND PAGES:
		 */
		public function wpoa_init_backend_scripts_styles() {

			// here we "localize" php variables, making them available as a js variable in the browser.
			$wpoa_cvars = array(
				// basic info.
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'template_directory'    => get_bloginfo( 'template_directory' ),
				'stylesheet_directory'  => get_bloginfo( 'stylesheet_directory' ),
				'plugins_url'           => plugins_url(),
				'plugin_dir_url'        => WPOA::$url,
				'url'                   => get_bloginfo( 'url' ),
				// other.
				'show_login_messages'   => get_option( 'wpoa_show_login_messages' ),
				'logout_inactive_users' => get_option( 'wpoa_logout_inactive_users' ),
				'logged_in'             => is_user_logged_in(),
			);

			// load the core plugin scripts/styles.
			wp_enqueue_script(
				'wpoa-script',
				WPOA::$url . 'assets/js/wp-oauth.js',
				array(),
				WPOA::$version,
				true
			);

			wp_localize_script(
				'wpoa-script',
				'wpoa_cvars',
				$wpoa_cvars
			);

			wp_enqueue_style(
				'wpoa-style',
				WPOA::$url . 'assets/css/wp-oauth.css',
				array(),
				WPOA::$version,
				'all'
			);
		}

		/**
		 * Init scripts and styles for use on the LOGIN PAGE:
		 */
		public function wpoa_init_login_scripts_styles() {

			// here we "localize" php variables, making them available as a js variable in the browser.
			$wpoa_cvars = array(
				// basic info.
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'template_directory'    => get_bloginfo( 'template_directory' ),
				'stylesheet_directory'  => get_bloginfo( 'stylesheet_directory' ),
				'plugins_url'           => plugins_url(),
				'plugin_dir_url'        => WPOA::$url,
				'url'                   => get_bloginfo( 'url' ),
				// login specific.
				'hide_login_form'       => get_option( 'wpoa_hide_wordpress_login_form' ),
				'logo_image'            => get_option( 'wpoa_logo_image' ),
				'bg_image'              => get_option( 'wpoa_bg_image' ),
				'login_message'         => $_SESSION['WPOA']['RESULT'],
				'show_login_messages'   => get_option( 'wpoa_show_login_messages' ),
				'logout_inactive_users' => get_option( 'wpoa_logout_inactive_users' ),
				'logged_in'             => is_user_logged_in(),
			);

			// load the core plugin scripts/styles.
			wp_enqueue_script(
				'wpoa-script',
				WPOA::$url . 'assets/js/wp-oauth.js',
				array(),
				WPOA::$version,
				true
			);

			wp_localize_script(
				'wpoa-script',
				'wpoa_cvars',
				$wpoa_cvars
			);

			wp_enqueue_style(
				'wpoa-style',
				WPOA::$url . 'assets/css/wp-oauth.css',
				array(),
				WPOA::$version,
				'all'
			);
		}
	}

	new WPOA_Enqueue();
}
