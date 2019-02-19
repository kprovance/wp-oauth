<?php
/**
 * WPOA main class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 *
 * @author Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA' ) ) {
	/**
	 * Class WPOA
	 */
	class WPOA {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public static $version = '';

		/**
		 * Plugin directory.
		 *
		 * @var string
		 */
		public static $dir = '';

		/**
		 * Plugin URI.
		 *
		 * @var string
		 */
		public static $url = '';

		/**
		 * Login class.
		 *
		 * @var null
		 */
		public static $login = null;

		/**
		 * Singleton class pattern.
		 *
		 * @var null
		 */
		protected static $instance = null;

		/**
		 * Define the settings used by this plugin; this array will be used for registering settings, applying default values, and deleting them during uninstall:
		 *
		 * @var array
		 */
		public static $settings = array(
			'wpoa_show_login_messages'              => 0,               // 0, 1.
			'wpoa_login_redirect'                   => 'home_page',     // home_page, last_page, specific_page, admin_dashboard, profile_page, custom_url.
			'wpoa_login_redirect_page'              => 0,               // any whole number (wordpress page id).
			'wpoa_login_redirect_url'               => '',              // any string (url).
			'wpoa_logout_redirect'                  => 'home_page',     // home_page, last_page, specific_page, admin_dashboard, profile_page, custom_url, default_handling.
			'wpoa_logout_redirect_page'             => 0,               // any whole number (wordpress page id).
			'wpoa_logout_redirect_url'              => '',              // any string (url).
			'wpoa_logout_inactive_users'            => 0,               // any whole number (minutes).
			'wpoa_hide_wordpress_login_form'        => 0,               // 0, 1.
			'wpoa_logo_links_to_site'               => 0,               // 0, 1.
			'wpoa_logo_image'                       => '',              // any string (image url).
			'wpoa_bg_image'                         => '',              // any string (image url).
			'wpoa_login_form_show_login_screen'     => 'Login Screen',  // any string (name of a custom login form shortcode design).
			'wpoa_login_form_show_profile_page'     => 'Profile Page',  // any string (name of a custom login form shortcode design).
			'wpoa_login_form_show_comments_section' => 'None',          // any string (name of a custom login form shortcode design).
			'wpoa_login_form_designs'               => array(           // array of shortcode designs to be included by default; same array signature as the shortcode function uses.
				'Login Screen' => array(
					'icon_set'          => 'none',
					'layout'            => 'buttons-column',
					'align'             => 'center',
					'show_login'        => 'conditional',
					'show_logout'       => 'conditional',
					'button_prefix'     => 'Login with',
					'logged_out_title'  => 'Please login:',
					'logged_in_title'   => 'You are already logged in.',
					'logging_in_title'  => 'Logging in...',
					'logging_out_title' => 'Logging out...',
					'style'             => '',
					'class'             => '',
				),
				'Profile Page' => array(
					'icon_set'          => 'none',
					'layout'            => 'buttons-row',
					'align'             => 'left',
					'show_login'        => 'always',
					'show_logout'       => 'never',
					'button_prefix'     => 'Link',
					'logged_out_title'  => 'Select a provider:',
					'logged_in_title'   => 'Select a provider:',
					'logging_in_title'  => 'Authenticating...',
					'logging_out_title' => 'Logging out...',
					'style'             => '',
					'class'             => '',
				),
			),
			'wpoa_suppress_welcome_email'           => 0,             // 0, 1.
			'wpoa_new_user_role'                    => 'contributor', // role.
			'wpoa_google_api_enabled'               => 0,             // 0, 1
			'wpoa_google_api_id'                    => '',            // any string.
			'wpoa_google_api_secret'                => '',            // any string.
			'wpoa_facebook_api_enabled'             => 0,             // 0, 1.
			'wpoa_facebook_api_id'                  => '',            // any string.
			'wpoa_facebook_api_secret'              => '',            // any string.
			'wpoa_linkedin_api_enabled'             => 0,             // 0, 1
			'wpoa_linkedin_api_id'                  => '',            // any string.
			'wpoa_linkedin_api_secret'              => '',            // any string.
			'wpoa_github_api_enabled'               => 0,             // 0, 1
			'wpoa_github_api_id'                    => '',            // any string.
			'wpoa_github_api_secret'                => '',            // any string.
			'wpoa_reddit_api_enabled'               => 0,             // 0, 1
			'wpoa_reddit_api_id'                    => '',            // any string.
			'wpoa_reddit_api_secret'                => '',            // any string.
			'wpoa_windowslive_api_enabled'          => 0,             // 0, 1
			'wpoa_windowslive_api_id'               => '',            // any string.
			'wpoa_windowslive_api_secret'           => '',            // any string.
			'wpoa_paypal_api_enabled'               => 0,             // 0, 1
			'wpoa_paypal_api_id'                    => '',            // any string.
			'wpoa_paypal_api_secret'                => '',            // any string.
			'wpoa_paypal_api_sandbox_mode'          => 0,             // 0, 1
			'wpoa_instagram_api_enabled'            => 0,             // 0, 1
			'wpoa_instagram_api_id'                 => '',            // any string.
			'wpoa_instagram_api_secret'             => '',            // any string.
			'wpoa_battlenet_api_enabled'            => 0,             // 0, 1
			'wpoa_battlenet_api_id'                 => '',            // any string.
			'wpoa_battlenet_api_secret'             => '',            // any string.
			'wpoa_slack_api_enabled'                => 0,             // 0, 1
			'wpoa_slack_api_id'                     => '',            // any string.
			'wpoa_slack_api_secret'                 => '',            // any string.
			'wpoa_http_util'                        => 'curl',        // curl, stream-context.
			'wpoa_http_util_verify_ssl'             => 1,             // 0, 1
			'wpoa_restore_default_settings'         => 0,             // 0, 1
			'wpoa_delete_settings_on_uninstall'     => 0,             // 0, 1
		);

		/**
		 * Class instance.
		 *
		 * @return WPOA|null
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();

				self::$dir = plugin_dir_path( __FILE__ );
				self::$url = plugin_dir_url( __FILE__ );

				self::$instance->includes();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Includes.
		 */
		private function includes() {
			include_once self::$dir . 'inc/class-wpoa-login.php';
			include_once self::$dir . 'inc/class-wpoa-logout.php';
			include_once self::$dir . 'inc/class-wpoa-enqueue.php';
			include_once self::$dir . 'inc/class-wpoa-admin.php';
			include_once self::$dir . 'inc/class-wpoa-messages.php';
			include_once self::$dir . 'inc/class-wpoa-ajax.php';
			include_once self::$dir . 'inc/class-wpoa-user-profile.php';
			include_once self::$dir . 'inc/class-wpoa-shortcodes.php';
			include_once self::$dir . 'inc/class-wpoa-comments.php';
		}

		/**
		 * Hooks.
		 */
		private function hooks() {

		}

		/**
		 * WPOA constructor.
		 */
		public function __construct() {
			// hook load event to handle any plugin updates.
			add_action( 'plugins_loaded', array( $this, 'update' ) );

			// hook init event to handle plugin initialization.
			add_action( 'init', array( $this, 'init' ) );
		}

		/**
		 * Do something during plugin activation.
		 */
		public static function activate() {}

		/**
		 * Do something during plugin deactivation.
		 */
		public static function deactivate() {}

		/**
		 * Do something during plugin update.
		 */
		public function update() {
			$plugin_version    = self::$version;
			$installed_version = get_option( 'wpoa_plugin_version' );

			if ( ! $installed_version || $installed_version <= 0 || $installed_version !== $plugin_version ) {
				// version mismatch, run the update logic...
				// add any missing options and set a default (usable) value.
				$this->wpoa_add_missing_settings();

				// set the new version so we don't trigger the update again.
				update_option( 'wpoa_plugin_version', $plugin_version );

				// create an admin notice.
				add_action( 'admin_notices', array( $this, 'wpoa_update_notice' ) );
			}
		}

		/**
		 * Adds any missing settings and their default values:
		 */
		private function wpoa_add_missing_settings() {
			foreach ( self::$settings as $setting_name => $default_value ) {

				// call add_option() which ensures that we only add NEW options that don't exist.
				if ( is_array( self::$settings[ $setting_name ] ) ) {
					$default_value = wp_json_encode( $default_value );
				}

				$added = add_option( $setting_name, $default_value );
			}
		}

		/**
		 * Indicate to the admin that the plugin has been updated.
		 */
		public function wpoa_update_notice() {
			$settings_link = "<a href='options-general.php?page=WP-OAuth.php'>Settings Page</a>"; // CASE SeNsItIvE filename!
			?>
			<div class="updated">
				<p>WP-OAuth has been updated! Please review the <?php echo $settings_link; // WPCS: XSS ok. ?>.</p>
			</div>
			<?php
		}

		/**
		 * Restores the default plugin settings:
		 */
		private function wpoa_restore_default_settings() {
			foreach ( self::$settings as $setting_name => $default_value ) {

				// call update_option() which ensures that we update the setting's value.
				if ( is_array( self::$settings[ $setting_name ] ) ) {
					$default_value = wp_json_encode( $default_value );
				}

				update_option( $setting_name, $default_value );
			}

			add_action( 'admin_notices', array( $this, 'wpoa_restore_default_settings_notice' ) );
		}

		/**
		 * Indicate to the admin that the plugin has been updated:
		 */
		public function wpoa_restore_default_settings_notice() {
			$settings_link = "<a href='options-general.php?page=WP-OAuth.php'>Settings Page</a>"; // CASE SeNsItIvE filename!

			?>
			<div class="updated">
				<p>The default settings have been restored. You may review the <?php echo $settings_link; // WPCS: XSS ok. ?>.</p>
			</div>
			<?php
		}

		/**
		 * Initialize the plugin's functionality by hooking into WordPress.
		 */
		public function init() {

			// restore default settings if necessary; this might get toggled by the admin or forced by a new version of the plugin.
			if ( get_option( 'wpoa_restore_default_settings' ) ) {
				$this->wpoa_restore_default_settings();
			}

			// hook the query_vars and template_redirect so we can stay within the WordPress context no matter what (avoids having to use wp-load.php).
			add_filter( 'query_vars', array( $this, 'qvar_triggers' ) );
			add_action( 'template_redirect', array( $this, 'qvar_handlers' ) );

			$plugin = plugin_basename( __FILE__ );
			add_filter( "plugin_action_links_$plugin", array( $this, 'settings_link' ) );
		}

		/**
		 * Define the querystring variables that should trigger an action.
		 *
		 * @param array $vars Vars.
		 *
		 * @return array
		 */
		public function qvar_triggers( $vars ) {
			$vars[] = 'connect';
			$vars[] = 'code';
			$vars[] = 'error_description';
			$vars[] = 'error_message';

			return $vars;
		}

		/**
		 * Add a settings link to the plugins page:
		 *
		 * @param array $links Links.
		 *
		 * @return mixed
		 */
		public function settings_link( $links ) {
			$settings_link = "<a href='options-general.php?page=WP-OAuth.php'>Settings</a>"; // CASE SeNsItIvE filename!

			array_unshift( $links, $settings_link );

			return $links;
		}

		/**
		 * Handle the querystring triggers.
		 */
		public function qvar_handlers() {
			if ( get_query_var( 'connect' ) ) {
				$provider = get_query_var( 'connect' );
				$this->wpoa_include_connector( $provider );
			} elseif ( get_query_var( 'code' ) ) {
				$provider = $_SESSION['WPOA']['PROVIDER'];
				$this->wpoa_include_connector( $provider );
			} elseif ( get_query_var( 'error_description' ) || get_query_var( 'error_message' ) ) {
				$provider = $_SESSION['WPOA']['PROVIDER'];
				$this->wpoa_include_connector( $provider );
			}
		}

		/**
		 * Load the provider script that is being requested by the user or being called back after authentication.
		 *
		 * @param string $provider Provider.
		 */
		private function wpoa_include_connector( $provider ) {

			// normalize the provider name (no caps, no spaces).
			$provider = strtolower( $provider );
			$provider = str_replace( ' ', '', $provider );
			$provider = str_replace( '.', '', $provider );

			// include the provider script.
			include self::$dir . 'inc/login/login-' . $provider . '.php';
		}

		/**
		 * Adds basic http auth to a given url string.
		 *
		 * @param string $url URL.
		 * @param string $username Username.
		 * @param string $password Password.
		 *
		 * @return mixed|string
		 */
		public static function add_basic_auth( $url, $username, $password ) {
			$url = str_replace( 'https://', '', $url );
			$url = 'https://' . $username . ':' . $password . '@' . $url;

			return $url;
		}
	}
}
