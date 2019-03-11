<?php
/**
 * WPOA Login class
 *
 * @package     WP-OAuth
 * @since       1.0.0
 * @author      Kevin Provance <kevin.provance@gmail.com>
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPOA_Login' ) ) {

	/**
	 * Class WPOA_Login
	 */
	class WPOA_Login {

		/**
		 * Gets the content to be used for displaying the login/logout form.
		 *
		 * @param array $args Design args.
		 *
		 * @return string
		 */
		public function login_form_content( $args ) {

			/* $design, $icon_set, $layout $button_prefix, $align, $show_login, $show_logout, $logged_out_title, $logged_in_title, $logging_in_title, $logging_out_title, $style, $class. */  // phpcs:ignore Squiz.PHP.CommentedOutCode

			// phpcs:ignore WordPress.PHP.DontExtract
			extract( $args );

			// Even though wpoa_login_form() will pass a default, we might call this function from another method so it's important to re-specify the default values.
			// If a design was specified and that design exists, load the shortcode attributes from that design.
			if ( '' !== $design && $this->login_form_design_exists( $design ) ) { // TODO: remove first condition not needed.
				$_SESSION['WPOA']['DESIGN'] = $design;

				$a                 = $this->get_login_form_design( $design );
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
					$html .= $this->login_buttons( $icon_set, $button_prefix );
				}

				if ( 'always' === $show_logout || 'conditional' === $show_logout ) {
					$html .= "<a class='wpoa-logout-button' href='" . wp_logout_url() . "' title='Logout'>Logout</a>";
				}
			} else {
				if ( $logged_out_title ) {
					$html .= '<p id="wpoa-title">' . $logged_out_title . '</p>';
				}

				if ( 'always' === $show_login || 'conditional' === $show_login ) {
					$html .= $this->login_buttons( $icon_set, $button_prefix );
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
		 * Form design exists.
		 *
		 * @param string $design_name Design Name.
		 *
		 * @return bool
		 */
		private function login_form_design_exists( $design_name ) {
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
		 * Returns a saved login form design as a shortcode atts string or array for direct use via the shortcode
		 *
		 * @param string $design_name Design Name.
		 * @param bool   $as_string As string.
		 *
		 * @return false|mixed|string|void
		 */
		public function get_login_form_design( $design_name, $as_string = false ) {
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
		 * Generate and return the login buttons, depending on available providers.
		 *
		 * @param string $icon_set Icon set.
		 * @param string $button_prefix Button prefix.
		 *
		 * @return string
		 */
		private function login_buttons( $icon_set, $button_prefix ) {

			// Generate the atts once (cache them), so we can use it for all buttons without computing them each time.
			$site_url    = get_bloginfo( 'url' );
			$redirect_to = isset( $_GET['redirect_to'] ) ? rawurlencode( sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) ) : ''; //  phpcs:ignore WordPress.Security.NonceVerification

			if ( '' !== $redirect_to ) {
				$redirect_to = '&redirect_to=' . $redirect_to;
			}
			// Get shortcode atts that determine how we should build these buttons.
			$icon_set_path = WPOA::$url . 'assets/img/' . $icon_set . '/';

			$atts = array(
				'site_url'      => $site_url,
				'redirect_to'   => $redirect_to,
				'icon_set'      => $icon_set,
				'icon_set_path' => $icon_set_path,
				'button_prefix' => $button_prefix,
			);

			// Generate the login buttons for available providers.
			// TODO: don't hard-code the buttons/providers here, we want to be able to add more providers without having to update this function...
			$services = WPOA::get_services();

			$html = '';
			foreach ( $services as $key => $title ) {
				if ( isset( $_SESSION['WPOA']['accounts'] ) && ! isset( $_SESSION['WPOA']['accounts'][ $key ] ) ) {
					$html .= $this->wpoa_login_button( $key, $title, $atts );
				}
			}

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
		private function login_button( $provider, $display_name, $atts ) {
			global $current_user;
			global $wpdb;

			// Get the current user.
			wp_get_current_user();
			$user_id = $current_user->ID;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$query_result = $wpdb->get_var( $wpdb->prepare( "SELECT $wpdb->usermeta.umeta_id FROM $wpdb->usermeta WHERE %d = $wpdb->usermeta.user_id AND $wpdb->usermeta.meta_key = 'wpoa_identity' AND $wpdb->usermeta.meta_value LIKE %s", $user_id, '%' . $provider . '%' ) );

			$disabled = '';
			if ( ! empty( $query_result ) ) {
				$disabled = ' disabled';
			}

			$html = '';

			if ( get_option( 'wpoa_' . $provider . '_api_enabled' ) ) {
				$html .= "<a id='wpoa-login-" . $provider . "' class='wpoa-login-button" . $disabled . ' ' . $query_result . "' href='" . $atts['site_url'] . '?connect=' . $provider . $atts['redirect_to'] . "'>";

				if ( 'none' !== $atts['icon_set'] ) {
					$html .= "<img src='" . $atts['icon_set_path'] . $provider . ".png' alt='" . $display_name . "' class='icon'></img>";
				}

				$html .= $atts['button_prefix'] . ' ' . $display_name;
				$html .= '</a>';
			}

			return $html;
		}

		/**
		 * Initiate login into WordPress.
		 *
		 * @param object $user WP_User.
		 */
		private function initiate_login( $user ) {
			// there was a matching WordPress user account, log it in now.
			$user_id    = $user->ID;
			$user_login = $user->user_login;

			wp_set_current_user( $user_id, $user_login );
			wp_set_auth_cookie( $user_id );

			do_action( 'wp_login', $user_login, $user );
		}

		/**
		 * Login (or register and login) a WordPress user based on their oauth identity.
		 *
		 * @param array $oauth_identity ID.
		 */
		public function login_user( $oauth_identity ) {

			// Store the user info in the user session so we can grab it later if we need to register the user.
			$_SESSION['WPOA']['USER_ID'] = $oauth_identity['id'];

			// try to find a matching WordPress user for the now-authenticated user's oauth identity.
			$matched_user = $this->match_wordpress_user( $oauth_identity );

			// handle the matched user if there is one.
			if ( $matched_user ) {
				$this->initiate_login( $matched_user );

				// after login, redirect to the user's last location.
				self::end_login( 'Logged in successfully!' );
			}

			// handle the already logged in user if there is one.
			if ( is_user_logged_in() ) {

				// there was a WordPress user logged in, but it is not associated with the now-authenticated user's email address, so associate it now.
				global $current_user;

				wp_get_current_user();
				$user_id = $current_user->ID;
				$this->link_account( $user_id, $oauth_identity );

				// after linking the account, redirect user to their last url.
				self::end_login( 'Your account was linked successfully with your third party authentication provider.' );
			}

			// handle the logged out user or no matching user (register the user).
			if ( ! is_user_logged_in() && ! $matched_user ) {

				$matched_user = $this->match_wordpress_user_email( $oauth_identity );

				if ( $matched_user ) {
					$this->initiate_login( $matched_user );

					// after login, redirect to the user's last location.
					self::end_login( 'Logged in successfully!' );
				} else {

					// this person is not logged into a WordPress account and has no third party authentications registered, so proceed to register the WordPress user.
					include WPOA::$dir . 'inc/wpoa-register.php';
				}
			}

			// we shouldn't be here, but just in case...
			self::end_login( 'Sorry, we couldn\'t log you in. The login flow terminated in an unexpected way. Please notify the admin or try again later.' );
		}

		/**
		 * Match the oauth identity to an existing WordPress user account by email.
		 *
		 * @param array $oauth_identity ID.
		 *
		 * @return bool|WP_User
		 */
		private function match_wordpress_user_email( $oauth_identity ) {
			if ( isset( $oauth_identity['email'] ) && '' !== $oauth_identity['email'] ) {
				$user = get_user_by( 'email', $oauth_identity['email'] );
			}

			return $user;
		}

		/**
		 * Match the oauth identity to an existing WordPress user account.
		 *
		 * @param array $oauth_identity ID.
		 *
		 * @return bool|WP_User
		 */
		private function match_wordpress_user( $oauth_identity ) {
			// attempt to get a WordPress user id from the database that matches the $oauth_identity['id'] value.
			global $wpdb;

			if ( ! isset( $oauth_identity['id'] ) ) {
				return;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$query_result = $wpdb->get_var( $wpdb->prepare( "SELECT $wpdb->usermeta.user_id FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'wpoa_identity' AND $wpdb->usermeta.meta_value LIKE %s", '%' . $wpdb->esc_like( $oauth_identity['provider'] ) . '|' . $wpdb->esc_like( $oauth_identity['id'] ) . '%' ) );

			// attempt to get a WordPress user with the matched id.
			$user = get_user_by( 'id', $query_result );

			return $user;
		}

		/**
		 * Ends the login request by clearing the login state and redirecting the user to the desired page.
		 *
		 * @param string $msg Message.
		 */
		public function end_login( $msg ) {
			global $current_user;
			global $wpdb;

			$last_url = $_SESSION['WPOA']['LAST_URL'];

			unset( $_SESSION['WPOA']['LAST_URL'] );

			$_SESSION['WPOA']['RESULT'] = $msg;

			WPOA::$login->clear_state();
			$redirect_method = get_option( 'wpoa_login_redirect' );

			$redirect_url = '';

			wp_get_current_user();
			$user_id = $current_user->ID;

			// Get the wpoa_identity records.
			$query_result = $wpdb->get_results( "SELECT * FROM $wpdb->usermeta WHERE $user_id = $usermeta$wpdb->usermeta_table.user_id AND $wpdb->usermeta.meta_key = 'wpoa_identity'" );

			$accounts = array();
			foreach ( $query_result as $item ) {
				$parts                               = explode( '|', $item->meta_value );
				$accounts[ strtolower( $parts[0] ) ] = $parts[1];
			}

			$_SESSION['WPOA']['accounts'] = $accounts;

			$redirect_override = apply_filters( 'wpoa_login_redirect_override', $_SESSION['WPOA']['DESIGN'], $last_url );

			if ( '' === $redirect_override ) {
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
			} else {
				$redirect_url = $redirect_override;
			}

			wp_safe_redirect( $redirect_url );

			die();
		}

		/**
		 * Links a third-party account to an existing WordPress user account.
		 *
		 * @param string $user_id User ID.
		 * @param array  $oauth_identity OAuth Identity.
		 */
		public function link_account( $user_id, $oauth_identity ) {
			if ( '' !== $_SESSION['WPOA']['USER_ID'] ) {
				add_user_meta( $user_id, 'wpoa_identity', $_SESSION['WPOA']['PROVIDER'] . '|' . $_SESSION['WPOA']['USER_ID'] . '|' . time() . '|' . $oauth_identity['email'] . '|' . $oauth_identity['name'] . '|' . $_SESSION['WPOA']['REFRESH_TOKEN'] );
			}
		}

		/**
		 * Clears the login state.
		 */
		public function clear_state() {
			unset( $_SESSION['WPOA']['USER_ID'] );
			unset( $_SESSION['WPOA']['USER_EMAIL'] );
			unset( $_SESSION['WPOA']['ACCESS_TOKEN'] );
			unset( $_SESSION['WPOA']['REFRESH_TOKEN'] );
			unset( $_SESSION['WPOA']['EXPIRES_IN'] );
			unset( $_SESSION['WPOA']['EXPIRES_AT'] );
		}

	}

	WPOA::$login = new WPOA_Login();
}
