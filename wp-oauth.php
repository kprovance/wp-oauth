<?php
/*
Plugin Name: WP-OAuth
Plugin URI: http://github.com/perrybutler/wp-oauth
Description: A WordPress plugin that allows users to login or register by authenticating with an existing Google, Facebook, LinkedIn, Github, Reddit or Windows Live account via OAuth 2.0. Easily drops into new or existing sites, integrates with existing users.
Version: 0.4.1
Author: Perry Butler
Author URI: http://glassocean.net
License: GPL2
*/

// start the user session for persisting user/login state during ajax, header redirect, and cross domain calls.
session_start();

/**
 * Class WPOA
 */
class WPOA {

	/**
	 * Set a version that we can use for performing plugin updates, this should always match the plugin version.
	 */
	const PLUGIN_VERSION = '0.4.1';

	/**
	 * Singleton class pattern.
	 *
	 * @var null
	 */
	protected static $instance = null;

	/**
	 * Class instance.
	 *
	 * @return WPOA|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Define the settings used by this plugin; this array will be used for registering settings, applying default values, and deleting them during uninstall:
	 *
	 * @var array
	 */
	private $settings = array(
		'wpoa_show_login_messages'              => 0,
		// 0, 1.
		'wpoa_login_redirect'                   => 'home_page',
		// home_page, last_page, specific_page, admin_dashboard, profile_page, custom_url.
		'wpoa_login_redirect_page'              => 0,
		// any whole number (wordpress page id).
		'wpoa_login_redirect_url'               => '',
		// any string (url).
		'wpoa_logout_redirect'                  => 'home_page',
		// home_page, last_page, specific_page, admin_dashboard, profile_page, custom_url, default_handling.
		'wpoa_logout_redirect_page'             => 0,
		// any whole number (wordpress page id).
		'wpoa_logout_redirect_url'              => '',
		// any string (url).
		'wpoa_logout_inactive_users'            => 0,
		// any whole number (minutes).
		'wpoa_hide_wordpress_login_form'        => 0,
		// 0, 1.
		'wpoa_logo_links_to_site'               => 0,
		// 0, 1.
		'wpoa_logo_image'                       => '',
		// any string (image url).
		'wpoa_bg_image'                         => '',
		// any string (image url).
		'wpoa_login_form_show_login_screen'     => 'Login Screen',
		// any string (name of a custom login form shortcode design).
		'wpoa_login_form_show_profile_page'     => 'Profile Page',
		// any string (name of a custom login form shortcode design).
		'wpoa_login_form_show_comments_section' => 'None',
		// any string (name of a custom login form shortcode design).
		// array of shortcode designs to be included by default; same array signature as the shortcode function uses.
		'wpoa_login_form_designs'               => array(
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
		'wpoa_suppress_welcome_email'           => 0,
		// 0, 1.
		'wpoa_new_user_role'                    => 'contributor',
		// role.
		'wpoa_google_api_enabled'               => 0,
		// 0, 1
		'wpoa_google_api_id'                    => '',
		// any string.
		'wpoa_google_api_secret'                => '',
		// any string.
		'wpoa_facebook_api_enabled'             => 0,
		// 0, 1.
		'wpoa_facebook_api_id'                  => '',
		// any string.
		'wpoa_facebook_api_secret'              => '',
		// any string.
		'wpoa_linkedin_api_enabled'             => 0,
		// 0, 1
		'wpoa_linkedin_api_id'                  => '',
		// any string.
		'wpoa_linkedin_api_secret'              => '',
		// any string.
		'wpoa_github_api_enabled'               => 0,
		// 0, 1
		'wpoa_github_api_id'                    => '',
		// any string.
		'wpoa_github_api_secret'                => '',
		// any string.
		'wpoa_reddit_api_enabled'               => 0,
		// 0, 1
		'wpoa_reddit_api_id'                    => '',
		// any string.
		'wpoa_reddit_api_secret'                => '',
		// any string.
		'wpoa_windowslive_api_enabled'          => 0,
		// 0, 1
		'wpoa_windowslive_api_id'               => '',
		// any string.
		'wpoa_windowslive_api_secret'           => '',
		// any string.
		'wpoa_paypal_api_enabled'               => 0,
		// 0, 1
		'wpoa_paypal_api_id'                    => '',
		// any string.
		'wpoa_paypal_api_secret'                => '',
		// any string.
		'wpoa_paypal_api_sandbox_mode'          => 0,
		// 0, 1
		'wpoa_instagram_api_enabled'            => 0,
		// 0, 1
		'wpoa_instagram_api_id'                 => '',
		// any string.
		'wpoa_instagram_api_secret'             => '',
		// any string.
		'wpoa_battlenet_api_enabled'            => 0,
		// 0, 1
		'wpoa_battlenet_api_id'                 => '',
		// any string.
		'wpoa_battlenet_api_secret'             => '',
		// any string.
		'wpoa_http_util'                        => 'curl',
		// curl, stream-context.
		'wpoa_http_util_verify_ssl'             => 1,
		// 0, 1
		'wpoa_restore_default_settings'         => 0,
		// 0, 1
		'wpoa_delete_settings_on_uninstall'     => 0,
		// 0, 1
	);

	/**
	 * WPOA constructor.
	 */
	public function __construct() {
		// hook activation and deactivation for the plugin.
		register_activation_hook( __FILE__, array( $this, 'wpoa_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'wpoa_deactivate' ) );

		// hook load event to handle any plugin updates.
		add_action( 'plugins_loaded', array( $this, 'wpoa_update' ) );

		// hook init event to handle plugin initialization.
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Do something during plugin activation.
	 */
	public function wpoa_activate() {}

	/**
	 * Do something during plugin deactivation.
	 */
	public function wpoa_deactivate() {}

	/**
	 * Do something during plugin update.
	 */
	public function wpoa_update() {
		$plugin_version    = self::PLUGIN_VERSION;
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
	 * Adds any missing settings and their default values:
	 */
	private function wpoa_add_missing_settings() {
		foreach ( $this->settings as $setting_name => $default_value ) {

			// call add_option() which ensures that we only add NEW options that don't exist.
			if ( is_array( $this->settings[ $setting_name ] ) ) {
				$default_value = wp_json_encode( $default_value );
			}

			$added = add_option( $setting_name, $default_value );
		}
	}

	/**
	 * Restores the default plugin settings:
	 */
	private function wpoa_restore_default_settings() {
		foreach ( $this->settings as $setting_name => $default_value ) {

			// call update_option() which ensures that we update the setting's value.
			if ( is_array( $this->settings[ $setting_name ] ) ) {
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
		add_filter( 'query_vars', array( $this, 'wpoa_qvar_triggers' ) );
		add_action( 'template_redirect', array( $this, 'wpoa_qvar_handlers' ) );

		// hook scripts and styles for frontend pages.
		add_action( 'wp_enqueue_scripts', array( $this, 'wpoa_init_frontend_scripts_styles' ) );

		// hook scripts and styles for backend pages.
		add_action( 'admin_enqueue_scripts', array( $this, 'wpoa_init_backend_scripts_styles' ) );
		add_action( 'admin_menu', array( $this, 'wpoa_settings_page' ) );
		add_action( 'admin_init', array( $this, 'wpoa_register_settings' ) );
		$plugin = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$plugin", array( $this, 'wpoa_settings_link' ) );

		// hook scripts and styles for login page.
		add_action( 'login_enqueue_scripts', array( $this, 'wpoa_init_login_scripts_styles' ) );

		if ( get_option( 'wpoa_logo_links_to_site' ) === '1' ) {
			add_filter( 'login_headerurl', array( $this, 'wpoa_logo_link' ) );
		}

		add_filter( 'login_message', array( $this, 'wpoa_customize_login_screen' ) );

		// hooks used globally.
		add_filter( 'comment_form_defaults', array( $this, 'wpoa_customize_comment_form_fields' ) );
		add_action( 'show_user_profile', array( $this, 'wpoa_linked_accounts' ) );
		add_action( 'wp_logout', array( $this, 'wpoa_end_logout' ) );
		add_action( 'wp_ajax_wpoa_logout', array( $this, 'wpoa_logout_user' ) );
		add_action( 'wp_ajax_wpoa_unlink_account', array( $this, 'wpoa_unlink_account' ) );
		add_action( 'wp_ajax_nopriv_wpoa_unlink_account', array( $this, 'wpoa_unlink_account' ) );
		add_shortcode( 'wpoa_login_form', array( $this, 'wpoa_login_form' ) );

		// push login messages into the DOM if the setting is enabled.
		if ( get_option( 'wpoa_show_login_messages' ) !== false ) {
			add_action( 'wp_footer', array( $this, 'wpoa_push_login_messages' ) );
			add_filter( 'admin_footer', array( $this, 'wpoa_push_login_messages' ) );
			add_filter( 'login_footer', array( $this, 'wpoa_push_login_messages' ) );
		}
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
			'plugin_dir_url'        => plugin_dir_url( __FILE__ ),
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
			plugin_dir_url( __FILE__ ) . 'wp-oauth.js',
			array(),
			self::PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'wpoa-script',
			'wpoa_cvars',
			$wpoa_cvars
		);

		wp_enqueue_style(
			'wpoa-style',
			plugin_dir_url( __FILE__ ) . 'wp-oauth.css',
			array(),
			self::PLUGIN_VERSION,
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
			'plugin_dir_url'        => plugin_dir_url( __FILE__ ),
			'url'                   => get_bloginfo( 'url' ),
			// other.
			'show_login_messages'   => get_option( 'wpoa_show_login_messages' ),
			'logout_inactive_users' => get_option( 'wpoa_logout_inactive_users' ),
			'logged_in'             => is_user_logged_in(),
		);

		// load the core plugin scripts/styles.
		wp_enqueue_script(
			'wpoa-script',
			plugin_dir_url( __FILE__ ) . 'wp-oauth.js',
			array(),
			self::PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'wpoa-script',
			'wpoa_cvars',
			$wpoa_cvars
		);

		wp_enqueue_style(
			'wpoa-style',
			plugin_dir_url( __FILE__ ) . 'wp-oauth.css',
			array(),
			self::PLUGIN_VERSION,
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
			'plugin_dir_url'        => plugin_dir_url( __FILE__ ),
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
			plugin_dir_url( __FILE__ ) . 'wp-oauth.js',
			array(),
			self::PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'wpoa-script',
			'wpoa_cvars',
			$wpoa_cvars
		);

		wp_enqueue_style(
			'wpoa-style',
			plugin_dir_url( __FILE__ ) . 'wp-oauth.css',
			array(),
			self::PLUGIN_VERSION,
			'all'
		);
	}

	/**
	 * Add a settings link to the plugins page:
	 *
	 * @param array $links Links.
	 *
	 * @return mixed
	 */
	public function wpoa_settings_link( $links ) {
		$settings_link = "<a href='options-general.php?page=WP-OAuth.php'>Settings</a>"; // CASE SeNsItIvE filename!

		array_unshift( $links, $settings_link );

		return $links;
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
	public function wpoa_add_basic_auth( $url, $username, $password ) {
		$url = str_replace( 'https://', '', $url );
		$url = 'https://' . $username . ':' . $password . '@' . $url;

		return $url;
	}

	/**
	 * Define the querystring variables that should trigger an action.
	 *
	 * @param array $vars Vars.
	 *
	 * @return array
	 */
	public function wpoa_qvar_triggers( $vars ) {
		$vars[] = 'connect';
		$vars[] = 'code';
		$vars[] = 'error_description';
		$vars[] = 'error_message';

		return $vars;
	}

	/**
	 * Handle the querystring triggers.
	 */
	public function wpoa_qvar_handlers() {
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
	public function wpoa_include_connector( $provider ) {

		// normalize the provider name (no caps, no spaces).
		$provider = strtolower( $provider );
		$provider = str_replace( ' ', '', $provider );
		$provider = str_replace( '.', '', $provider );

		// include the provider script.
		include 'login-' . $provider . '.php';
	}

	/**
	 * Match the oauth identity to an existing WordPress user account.
	 *
	 * @param array $oauth_identity ID.
	 *
	 * @return bool|WP_User
	 */
	public function wpoa_match_wordpress_user( $oauth_identity ) {
		// attempt to get a WordPress user id from the database that matches the $oauth_identity['id'] value.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$query_result = $wpdb->get_var( $wpdb->prepare( "SELECT $wpdb->usermeta.user_id FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'wpoa_identity' AND $wpdb->usermeta.meta_value LIKE %s", '%' . $wpdb->esc_like( $oauth_identity['provider'] ) . '|' . $wpdb->esc_like( $oauth_identity['id'] ) . '%' ) );

		// attempt to get a WordPress user with the matched id.
		$user = get_user_by( 'id', $query_result );

		return $user;
	}

	/**
	 * Login (or register and login) a WordPress user based on their oauth identity.
	 *
	 * @param array $oauth_identity ID.
	 */
	public function wpoa_login_user( $oauth_identity ) {

		// Store the user info in the user session so we can grab it later if we need to register the user.
		$_SESSION['WPOA']['USER_ID'] = $oauth_identity['id'];

		// try to find a matching WordPress user for the now-authenticated user's oauth identity.
		$matched_user = $this->wpoa_match_wordpress_user( $oauth_identity );

		// handle the matched user if there is one.
		if ( $matched_user ) {

			// there was a matching WordPress user account, log it in now.
			$user_id    = $matched_user->ID;
			$user_login = $matched_user->user_login;
			wp_set_current_user( $user_id, $user_login );
			wp_set_auth_cookie( $user_id );
			do_action( 'wp_login', $user_login, $matched_user );

			// after login, redirect to the user's last location.
			$this->wpoa_end_login( 'Logged in successfully!' );
		}

		// handle the already logged in user if there is one.
		if ( is_user_logged_in() ) {

			// there was a WordPress user logged in, but it is not associated with the now-authenticated user's email address, so associate it now.
			global $current_user;

			wp_get_curren_user();
			$user_id = $current_user->ID;
			$this->wpoa_link_account( $user_id );

			// after linking the account, redirect user to their last url.
			$this->wpoa_end_login( 'Your account was linked successfully with your third party authentication provider.' );
		}

		// handle the logged out user or no matching user (register the user).
		if ( ! is_user_logged_in() && ! $matched_user ) {

			// this person is not logged into a WordPress account and has no third party authentications registered, so proceed to register the WordPress user.
			include 'register.php';
		}

		// we shouldn't be here, but just in case...
		$this->wpoa_end_login( 'Sorry, we couldn\'t log you in. The login flow terminated in an unexpected way. Please notify the admin or try again later.' );
	}

	/**
	 * Ends the login request by clearing the login state and redirecting the user to the desired page.
	 *
	 * @param string $msg Message.
	 */
	private function wpoa_end_login( $msg ) {
		$last_url = $_SESSION['WPOA']['LAST_URL'];

		unset( $_SESSION['WPOA']['LAST_URL'] );

		$_SESSION['WPOA']['RESULT'] = $msg;

		$this->wpoa_clear_login_state();
		$redirect_method = get_option( 'wpoa_login_redirect' );

		$redirect_url = '';

		switch ( $redirect_method ) {
			case 'home_page':
				$redirect_url = site_url();
				break;
			case 'last_page':
				$redirect_url = $last_url;
				break;
			case 'specific_page':
				$redirect_url = get_permalink( get_option( 'wpoa_login_redirect_page' ) );
				break;
			case 'admin_dashboard':
				$redirect_url = admin_url();
				break;
			case 'user_profile':
				$redirect_url = get_edit_user_link();
				break;
			case 'custom_url':
				$redirect_url = get_option( 'wpoa_login_redirect_url' );
				break;
		}

		wp_safe_redirect( $redirect_url );

		die();
	}


	/**
	 * Logout the WordPress user.
	 *
	 * TODO: this is usually called from a custom logout button, but we could have the button call /wp-logout.php?action=logout for more consistency...
	 */
	public function wpoa_logout_user() {

		// logout the user.
		$user = null;         // nullify the user.
		session_destroy();    // destroy the php user session.

		wp_logout();          // logout the WordPress user...this gets hooked and diverted to wpoa_end_logout() for final handling.
	}

	/**
	 * Ends the logout request by redirecting the user to the desired page.
	 *
	 * @return bool
	 */
	public function wpoa_end_logout() {
		$_SESSION['WPOA']['RESULT'] = 'Logged out successfully.';

		if ( is_user_logged_in() ) {

			// user is logged in and trying to logout...get their Last Page.
			$last_url = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		} else {

			// user is NOT logged in and trying to logout...get their Last Page minus the querystring so we don't trigger the logout confirmation.
			$last_url = isset( $_SERVER['HTTP_REFERER'] ) ? strtok( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'], '?' ) ) ) : '';
		}

		unset( $_SESSION['WPOA']['LAST_URL'] );

		$this->wpoa_clear_login_state();
		$redirect_method = get_option( 'wpoa_logout_redirect' );
		$redirect_url    = '';

		switch ( $redirect_method ) {
			case 'default_handling':
				return false;
			case 'home_page':
				$redirect_url = site_url();
				break;
			case 'last_page':
				$redirect_url = $last_url;
				break;
			case 'specific_page':
				$redirect_url = get_permalink( get_option( 'wpoa_logout_redirect_page' ) );
				break;
			case 'admin_dashboard':
				$redirect_url = admin_url();
				break;
			case 'user_profile':
				$redirect_url = get_edit_user_link();
				break;
			case 'custom_url':
				$redirect_url = get_option( 'wpoa_logout_redirect_url' );
				break;
		}

		wp_safe_redirect( $redirect_url );

		die();
	}

	/**
	 * Links a third-party account to an existing WordPress user account.
	 *
	 * @param mixed $user_id User ID.
	 */
	private function wpoa_link_account( $user_id ) {
		if ( '' !== $_SESSION['WPOA']['USER_ID'] ) {
			add_user_meta( $user_id, 'wpoa_identity', $_SESSION['WPOA']['PROVIDER'] . '|' . $_SESSION['WPOA']['USER_ID'] . '|' . time() );
		}
	}

	/**
	 * Unlinks a third-party provider from an existing WordPress user account.
	 */
	public function wpoa_unlink_account() {
		global $current_user;
		global $wpdb;

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'], 'wpoa-unlink-nonce' ) ) ) ) {

			// get wpoa_identity row index that the user wishes to unlink.
			$wpoa_identity_row = isset( $_POST['wpoa_identity_row'] ) ? sanitize_text_field( wp_unslash( $_POST['wpoa_identity_row'] ) ) : '';

			if ( '' !== $wpoa_identity_row ) {

				// Get the current user.
				wp_get_current_user();
				$user_id = $current_user->ID;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$query_result = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE $wpdb->usermeta.user_id = %d AND $wpdb->usermeta.meta_key = 'wpoa_identity' AND $wpdb->usermeta.umeta_id = %d", $user_id, $wpoa_identity_row ) );
			}
		}

		// Notify client of the result.
		if ( $query_result ) {
			echo wp_json_encode( array( 'result' => 1 ) );
		} else {
			echo wp_json_encode( array( 'result' => 0 ) );
		}

		die();
	}

	/**
	 * Pushes login messages into the dom where they can be extracted by javascript
	 */
	public function wpoa_push_login_messages() {
		$result                     = $_SESSION['WPOA']['RESULT'];
		$_SESSION['WPOA']['RESULT'] = '';

		echo '<div id="wpoa-result">' . esc_html( $result ) . '</div>';
	}

	/**
	 * Clears the login state.
	 */
	private function wpoa_clear_login_state() {
		unset( $_SESSION['WPOA']['USER_ID'] );
		unset( $_SESSION['WPOA']['USER_EMAIL'] );
		unset( $_SESSION['WPOA']['ACCESS_TOKEN'] );
		unset( $_SESSION['WPOA']['EXPIRES_IN'] );
		unset( $_SESSION['WPOA']['EXPIRES_AT'] );
	}

	/**
	 * Force the login screen logo to point to the site instead of wordpress.org:
	 *
	 * @return string|void
	 */
	public function wpoa_logo_link() {
		return get_bloginfo( 'url' );
	}

	/**
	 * Show a custom login form on the default login screen:
	 */
	public function wpoa_customize_login_screen() {
		$html   = '';
		$design = get_option( 'wpoa_login_form_show_login_screen' );

		if ( 'None' !== $design ) {
			// TODO: we need to use $settings defaults here, not hard-coded defaults...
			$html .= $this->wpoa_login_form_content( $design, 'none', 'buttons-column', 'Connect with', 'center', 'conditional', 'conditional', 'Please login:', 'You are already logged in.', 'Logging in...', 'Logging out...' );
		}
		echo $html; // WPCS: XSS ok.
	}

	/**
	 * Show a custom login form at the top of the default comment form.
	 *
	 * @param array $fields Fields.
	 *
	 * @return mixed
	 */
	public function wpoa_customize_comment_form_fields( $fields ) {
		$html   = '';
		$design = get_option( 'wpoa_login_form_show_comments_section' );

		if ( 'None' !== $design ) {

			// TODO: we need to use $settings defaults here, not hard-coded defaults...
			$html                  .= $this->wpoa_login_form_content( $design, 'none', 'buttons-column', 'Connect with', 'center', 'conditional', 'conditional', 'Please login:', 'You are already logged in.', 'Logging in...', 'Logging out...' );
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
			$html .= $this->wpoa_login_form_content( $design, 'none', 'buttons-column', 'Connect with', 'center', 'conditional', 'conditional', 'Please login:', 'You are already logged in.', 'Logging in...', 'Logging out...' );
		}

		echo $html; // WPCS: XSS ok.
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
		$html = $this->wpoa_login_form_content( $a['design'], $a['icon_set'], $a['layout'], $a['button_prefix'], $a['align'], $a['show_login'], $a['show_logout'], $a['logged_out_title'], $a['logged_in_title'], $a['logging_in_title'], $a['logging_out_title'], $a['style'], $a['class'] );

		return $html;
	}

	/**
	 * Gets the content to be used for displaying the login/logout form.
	 *
	 * @param string $design Design.
	 * @param string $icon_set Icon set.
	 * @param string $layout Layout.
	 * @param string $button_prefix Button prefix.
	 * @param string $align Align.
	 * @param string $show_login Show login.
	 * @param string $show_logout Show logout.
	 * @param string $logged_out_title Logged out title.
	 * @param string $logged_in_title Logged in title.
	 * @param string $logging_in_title Loggin in title.
	 * @param string $logging_out_title Logging out title.
	 * @param string $style Style.
	 * @param string $class Class.
	 *
	 * @return string
	 */
	private function wpoa_login_form_content( $design = '', $icon_set = 'icon_set', $layout = 'links-column', $button_prefix = '', $align = 'left', $show_login = 'conditional', $show_logout = 'conditional', $logged_out_title = 'Please login:', $logged_in_title = 'You are already logged in.', $logging_in_title = 'Logging in...', $logging_out_title = 'Logging out...', $style = '', $class = '' ) {
		// Even though wpoa_login_form() will pass a default, we might call this function from another method so it's important to re-specify the default values.
		// If a design was specified and that design exists, load the shortcode attributes from that design.
		if ( '' !== $design && self::wpoa_login_form_design_exists( $design ) ) { // TODO: remove first condition not needed.
			$a                 = self::wpoa_get_login_form_design( $design );
			$icon_set          = $a['icon_set'];
			$layout            = $a['layout'];
			$button_prefix     = $a['button_prefix'];
			$align             = $a['align'];
			$show_login        = $a['show_login'];
			$show_logout       = $a['show_logout'];
			$logged_out_title  = $a['logged_out_title'];
			$logged_in_title   = $a['logged_in_title'];
			$logging_in_title  = $a['logging_in_title'];
			$logging_out_title = $a['logging_out_title'];
			$style             = $a['style'];
			$class             = $a['class'];
		}

		// Build the shortcode markup.
		$html  = '';
		$html .= '<div class="wpoa-login-form wpoa-layout-' . $layout . ' wpoa-layout-align-' . $align . ' ' . $class . '" style="' . $style . '" data-logging-in-title="' . $logging_in_title . '" data-logging-out-title="' . $logging_out_title . '">';
		$html .= '<nav>';

		if ( is_user_logged_in() ) {
			if ( $logged_in_title ) {
				$html .= '<p id="wpoa-title">' . $logged_in_title . '</p>';
			}

			if ( 'always' === $show_login ) {
				$html .= $this->wpoa_login_buttons( $icon_set, $button_prefix );
			}

			if ( 'always' === $show_logout || 'conditional' === $show_logout ) {
				$html .= "<a class='wpoa-logout-button' href='" . wp_logout_url() . "' title='Logout'>Logout</a>";
			}
		} else {
			if ( $logged_out_title ) {
				$html .= '<p id="wpoa-title">' . $logged_out_title . '</p>';
			}

			if ( 'always' === $show_login || 'conditional' === $show_login ) {
				$html .= $this->wpoa_login_buttons( $icon_set, $button_prefix );
			}

			if ( 'always' === $show_logout ) {
				$html .= '<a class="wpoa-logout-button" href="' . wp_logout_url() . '" title="Logout">Logout</a>';
			}
		}
		$html .= '</nav>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Generate and return the login buttons, depending on available providers.
	 *
	 * @param string $icon_set Icon set.
	 * @param string $button_prefix Button prefix.
	 *
	 * @return string
	 */
	private function wpoa_login_buttons( $icon_set, $button_prefix ) {

		// Generate the atts once (cache them), so we can use it for all buttons without computing them each time.
		$site_url    = get_bloginfo( 'url' );
		$redirect_to = isset( $_GET['redirect_to'] ) ? rawurlencode( sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) ) : ''; // WPCS: CSRF ok.

		if ( '' !== $redirect_to ) {
			$redirect_to = '&redirect_to=' . $redirect_to;
		}
		// Get shortcode atts that determine how we should build these buttons.
		$icon_set_path = plugins_url( 'icons/' . $icon_set . '/', __FILE__ );

		$atts = array(
			'site_url'      => $site_url,
			'redirect_to'   => $redirect_to,
			'icon_set'      => $icon_set,
			'icon_set_path' => $icon_set_path,
			'button_prefix' => $button_prefix,
		);

		// Generate the login buttons for available providers.
		// TODO: don't hard-code the buttons/providers here, we want to be able to add more providers without having to update this function...
		$html  = '';
		$html .= $this->wpoa_login_button( 'google', 'Google', $atts );
		$html .= $this->wpoa_login_button( 'facebook', 'Facebook', $atts );
		$html .= $this->wpoa_login_button( 'linkedin', 'LinkedIn', $atts );
		$html .= $this->wpoa_login_button( 'github', 'GitHub', $atts );
		$html .= $this->wpoa_login_button( 'reddit', 'Reddit', $atts );
		$html .= $this->wpoa_login_button( 'windowslive', 'Windows Live', $atts );
		$html .= $this->wpoa_login_button( 'paypal', 'PayPal', $atts );
		$html .= $this->wpoa_login_button( 'instagram', 'Instagram', $atts );
		$html .= $this->wpoa_login_button( 'battlenet', 'Battlenet', $atts );

		if ( '' === $html ) {
			$html .= 'Sorry, no login providers have been enabled.';
		}

		return $html;
	}

	/**
	 * Generates and returns a login button for a specific provider:
	 *
	 * @param string $provider Provider.
	 * @param string $display_name Display Name.
	 * @param array  $atts Attributes.
	 *
	 * @return string
	 */
	private function wpoa_login_button( $provider, $display_name, $atts ) {
		$html = '';

		if ( get_option( 'wpoa_' . $provider . '_api_enabled' ) ) {
			$html .= "<a id='wpoa-login-" . $provider . "' class='wpoa-login-button' href='" . $atts['site_url'] . '?connect=' . $provider . $atts['redirect_to'] . "'>";

			if ( 'none' !== $atts['icon_set'] ) {
				$html .= "<img src='" . $atts['icon_set_path'] . $provider . ".png' alt='" . $display_name . "' class='icon'></img>";
			}

			$html .= $atts['button_prefix'] . ' ' . $display_name;
			$html .= '</a>';
		}

		return $html;
	}

	/**
	 * Output the custom login form design selector
	 *
	 * @param string $id ID.
	 * @param bool   $master Master.
	 *
	 * @return string
	 */
	public function wpoa_login_form_designs_selector( $id = '', $master = false ) {
		$html          = '';
		$designs_json  = get_option( 'wpoa_login_form_designs' );
		$designs_array = json_decode( $designs_json );
		$name          = str_replace( '-', '_', $id );
		$html         .= '<select id="' . $id . '" name="' . $name . '">';

		if ( true === $master ) {
			foreach ( $designs_array as $key => $val ) {
				$html .= '<option value="">' . $key . '"</option>"';
			}

			$html .= '</select>';
			$html .= '<input type="hidden" id="wpoa-login-form-designs" name="wpoa_login_form_designs" value="' . $designs_json . '">';
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
	 * Returns a saved login form design as a shortcode atts string or array for direct use via the shortcode
	 *
	 * @param string $design_name Design Name.
	 * @param bool   $as_string As string.
	 *
	 * @return false|mixed|string|void
	 */
	public function wpoa_get_login_form_design( $design_name, $as_string = false ) {
		$designs_json  = get_option( 'wpoa_login_form_designs' );
		$designs_array = json_decode( $designs_json, true );

		foreach ( $designs_array as $key => $val ) {
			if ( $design_name === $key ) {
				$found = $val;
				break;
			}
		}

		if ( $found ) {
			if ( $as_string ) {
				$atts = wp_json_encode( $found );
			} else {
				$atts = $found;
			}
		}

		return $atts;
	}

	/**
	 * Form design exists.
	 *
	 * @param string $design_name Design Name.
	 *
	 * @return bool
	 */
	private function wpoa_login_form_design_exists( $design_name ) {
		$designs_json  = get_option( 'wpoa_login_form_designs' );
		$designs_array = json_decode( $designs_json, true );

		foreach ( $designs_array as $key => $val ) {
			if ( $design_name === $key ) {
				$found = $val;
				break;
			}
		}
		if ( $found ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Shows the user's linked providers, used on the 'Your Profile' page:
	 */
	public function wpoa_linked_accounts() {
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
			$local_time          = strtotime( '-' . sanitize_text_field( wp_unslash( $_COOKIE['gmtoffset'] ) ) . ' hours', $time_linked ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$nonce               = wp_create_nonce( 'wpoa-unlink-nonce' );

			echo '<div>' . esc_html( $oauth_provider ) . ' on ' . esc_html( date( 'F d, Y h:i A', $local_time ) ) . ' <a class="wpoa-unlink-account" data-nonce="' . esc_attr( $nonce ) . '" data-wpoa-identity-row="' . esc_attr( $wpoa_row->umeta_id ) . '" href="#">Unlink</a></div>';
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
			echo $this->wpoa_login_form_content( $design, 'none', 'buttons-row', 'Link', 'left', 'always', 'never', 'Select a provider:', 'Select a provider:', 'Authenticating...', '' ); // WPCS: XSS ok.
		}

		echo '</div>';
		echo '</td>';
		echo '</td>';
		echo '</table>';
	}

	/**
	 * Registers all settings that have been defined at the top of the plugin
	 */
	public function wpoa_register_settings() {
		foreach ( $this->settings as $setting_name => $default_value ) {
			register_setting( 'wpoa_settings', $setting_name );
		}
	}

	/**
	 * Add the main settings page:
	 */
	public function wpoa_settings_page() {
		add_options_page(
			'WP-OAuth Options',
			'WP-OAuth',
			'manage_options',
			'WP-OAuth',
			array(
				$this,
				'wpoa_settings_page_content',
			)
		);
	}

	/**
	 * Render the main settings page content:
	 */
	public function wpoa_settings_page_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-oauth' ) );
		}

		$blog_url = rtrim( site_url(), '/' ) . '/';

		include 'wp-oauth-settings.php';
	}
}

// Instantiate the plugin class ONCE and maintain a single instance (singleton).
WPOA::get_instance();
