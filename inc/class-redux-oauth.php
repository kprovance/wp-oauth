<?php
/**
 * Redux oAuth Class
 *
 * @class   Redux_OAuth
 * @version 1.0.0
 * @package WP-OAuth
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Redux_OAuth', false ) ) {
	/**
	 * Class Redux_oAuth
	 */
	class Redux_OAuth {

		/**
		 * Instance.
		 *
		 * @var null
		 */
		private static $instance = null;

		/**
		 * Parent pointer.
		 *
		 * @var null
		 */
		private $parent = null;

		/**
		 * Config array.
		 *
		 * @var array
		 */
		public $config = array();

		/**
		 * Redux_oAuth constructor.
		 *
		 * @param object $parent Pointer.
		 */
		private function __construct( $parent ) {
			// Start the user session for maintaining individual user states during the multi-stage authentication flow.
			if ( ! isset( $_SESSION ) ) {
				session_start();
			}

			$this->parent = $parent;
		}

		/**
		 * Auth flow.
		 */
		public function auth_flow() {
			/* AUTHENTICATION FLOW */

			// The oauth 2.0 authentication flow will start in this script and make several calls to the third-party
			// authentication provider which in turn will make callbacks to this script that we continue to handle until
			// the login completes with a success or failure.
			if ( ! $this->config['client_enabled'] ) {
				WPOA::$login->end_login( 'This third-party authentication provider has not been enabled. Please notify the admin or try again later.' );
			} elseif ( ! $this->config['client_id'] || ! $this->config['client_secret'] ) {

				// Do not proceed if id or secret is null.
				WPOA::$login->end_login( 'This third-party authentication provider has not been configured with an API key/secret. Please notify the admin or try again later.' );
			} elseif ( isset( $_GET['error_description'] ) ) {

				// do not proceed if an error was detected.
				WPOA::$login->end_login( sanitize_text_field( wp_unslash( $_GET['error_description'] ) ) ); // WPCS: CSRF ok.
			} elseif ( isset( $_GET['error_message'] ) ) {

				// do not proceed if an error was detected.
				WPOA::$login->end_login( sanitize_text_field( wp_unslash( $_GET['error_message'] ) ) ); // WPCS: CSRF ok.
			} elseif ( isset( $_GET['code'] ) ) { // WPCS: CSRF ok.

				// post-auth phase, verify the state.
				if ( isset( $_GET['state'] ) && $_GET['state'] === $_SESSION['WPOA']['STATE'] ) { // WPCS: CSRF ok.

					// get an access token from the third party provider.
					$this->get_oauth_token( sanitize_text_field( wp_unslash( $_GET['code'] ) ) ); // WPCS: CSRF ok.

					// get the user's third-party identity and attempt to login/register a matching WordPress user account.
					$login_callback = 'WPOA_' . $this->config['provider'] . '_get_oauth_identity';

					if ( has_action( $login_callback ) ) {
						$oauth_identity = apply_filters( $login_callback, $this );
					} else {
						$oauth_identity = $this->get_oauth_identity();
					}

					if ( ! isset( $oauth_identity['id'] ) && ! isset( $oauth_identity['user']['id'] ) ) {
						WPOA::$login->end_login( 'Sorry, we couldn\'t log you in. User identity was not found. Please notify the admin or try again later.' );
					}

					WPOA::$login->login_user( $oauth_identity );
				} else {

					// possible CSRF attack, end the login with a generic message to the user and a detailed message to the admin/logs in case of abuse:
					// TODO: report detailed message to admin/logs here...
					WPOA::$login->end_login( "Sorry, we couldn't log you in. Please notify the admin or try again later." );
				}
			} else {

				// pre-auth, start the auth process.
				if ( ( empty( $_SESSION['WPOA']['EXPIRES_AT'] ) ) || ( time() > $_SESSION['WPOA']['EXPIRES_AT'] ) ) {

					// expired token; clear the state.
					WPOA::$login->clear_state();
				}

				$this->get_oauth_code();
			} // WPCS: CSRF ok.

			// we shouldn't be here, but just in case...
			WPOA::$login->end_login( 'Sorry, we couldn\'t log you in. The authentication flow terminated in an unexpected way. Please notify the admin or try again later.' );

			/* END OF AUTHENTICATION FLOW */
		}

		/**
		 * Set config.
		 *
		 * @param array $array Array.
		 */
		public function set_config( $array ) {
			$this->config = $array;

			// Outside of the scripted config.
			$this->config['http_util']       = get_option( 'wpoa_http_util' );
			$this->config['client_enabled']  = get_option( 'wpoa_' . strtolower( $this->config['provider'] ) . '_api_enabled' );
			$this->config['client_id']       = get_option( 'wpoa_' . $this->config['provider'] . '_api_id' );
			$this->config['client_secret']   = get_option( 'wpoa_' . $this->config['provider'] . '_api_secret' );
			$this->config['redirect_uri']    = rtrim( site_url(), '/' ) . '/';
			$this->config['util_verify_ssl'] = get_option( 'wpoa_http_util_verify_ssl' );

			$_SESSION['WPOA']['PROVIDER'] = ucfirst( $this->config['provider'] );

			// Remember the user's last url so we can redirect them back to there after the login ends.
			if ( ! isset( $_SESSION['WPOA']['LAST_URL'] ) ) {
				$_SESSION['WPOA']['LAST_URL'] = isset( $_SERVER['HTTP_REFERER'] ) ? strtok( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), '?' ) : $this->config['redirect_uri'];
			}
		}

		/**
		 * The object is created from within the class itself only if the class has no instance.
		 *
		 * @param object $parent Pointer.
		 *
		 * @return Redux_oAuth|null
		 */
		public static function instance( $parent ) {
			if ( null === self::$instance ) {
				self::$instance = new Redux_oAuth( $parent );
			}

			return self::$instance;
		}

		/* AUTHENTICATION FLOW HELPER FUNCTIONS */

		/**
		 * Get OAuth code.
		 */
		private function get_oauth_code() {
			$params = array(
				'response_type' => 'code',
				'client_id'     => $this->config['client_id'],
				'scope'         => $this->config['scope'],
				'state'         => uniqid( '', true ),
				'redirect_uri'  => $this->config['redirect_uri'],
			);

			$_SESSION['WPOA']['STATE'] = sanitize_text_field( wp_unslash( $params['state'] ) );
			$url                       = $this->config['url_auth'] . http_build_query( $params );

			header( "Location: $url" );

			exit;
		}

		/**
		 * Function Remote post.
		 *
		 * @param array  $params Params.
		 * @param string $url    URL.
		 * @param bool   $post   Is post.
		 *
		 * @return bool|string
		 */
		public function remote_post( $params, $url, $post = false ) {
			if ( isset( $this->config['authorization_header'] ) && isset( $params['access_token'] ) ) {
				$headr = 'Authorization: ' . $this->config['authorization_header'] . ' ' . $params['access_token'];
				unset( $params['access_token'] );
			}

			if ( is_array( $params ) && count( $params ) ) {
				$url_params = http_build_query( $params );
				$url        = $url . $url_params;
			}

			$sslverify = ( '1' === $this->config['util_verify_ssl'] ) ? true : false;

			if ( false === $post ) {
				$method = 'GET';
				$body   = null;
			} else {
				$method = 'POST';
				$body   = is_array( $params ) ? wp_json_encode( $params ) : $params;
			}

			$args = array(
				'method'      => $method,
				'user-agent'  => 'Redux OAuth/' . WPOA::$version . '; ' . get_bloginfo( 'url' ),
				'timeout'     => 45,
				'httpversion' => '1.1',
				'sslverify'   => $sslverify,
				'headers'     => $headr,
				'body'        => $body,
			);

			if ( false === $post ) {
				$result = wp_remote_get( $url, $args );

			} else {
				$result = wp_remote_post( $url, $args );
			}

			if ( ! is_wp_error( $result ) && 200 === wp_remote_retrieve_response_code( $result ) ) {
				$result = wp_remote_retrieve_body( $result );
			} else {
				$result = '';
			}

			return $result;
		}

		/**
		 * Get OAuth token.
		 *
		 * @param string $code Token.
		 *
		 * @return bool
		 */
		private function get_oauth_token( $code ) {
			$params = array(
				'grant_type'    => 'authorization_code',
				'client_id'     => $this->config['client_id'],
				'client_secret' => $this->config['client_secret'],
				'code'          => $code,
				'redirect_uri'  => $this->config['redirect_uri'],
			);

			$url = $this->config['url_token'];
			if ( isset( $this->config['get_oauth_token']['params_as_string'] ) && $this->config['get_oauth_token']['params_as_string'] ) {
				$params = http_build_query( $params );
			}

			$result_obj = $this->remote_post( $params, $url, true );

			if ( isset( $this->config['get_oauth_token']['json_decode'] ) && true === $this->config['get_oauth_token']['json_decode'] ) {
				$result_obj = json_decode( $result_obj, true );
			}

			if ( isset( $this->config['get_oauth_token']['parse_str'] ) && true === $this->config['get_oauth_token']['parse_str'] ) {
				parse_str($result_obj, $result);
				$result_obj = $result;
			} 

			// process the result.
			$access_token = isset( $result_obj[ $this->config['get_oauth_token']['access_token'] ] ) ? $result_obj[ $this->config['get_oauth_token']['access_token'] ] : false;
			$expires_in   = isset( $result_obj[ $this->config['get_oauth_token']['expires_in'] ] ) ? $result_obj[ $this->config['get_oauth_token']['expires_in'] ] : 5179152;

			$refresh_token = '';

			if ( isset( $this->config['get_oauth_token']['refresh_token'] ) && isset( $result_obj[ $this->config['get_oauth_token']['refresh_token'] ] ) ) {
				$refresh_token = isset( $result_obj[ $this->config['get_oauth_token']['refresh_token'] ] ) ? $result_obj[ $this->config['get_oauth_token']['refresh_token'] ] : false;
			}

			$expires_at = time() + $expires_in;


			// handle the result.
			if ( ! $access_token || ! $expires_in ) {

				// malformed access token result detected.
				WPOA::$login->end_login( "Sorry, we couldn't log you in. Malformed access token result detected. Please notify the admin or try again later." );
			} else {
				$_SESSION['WPOA']['ACCESS_TOKEN']  = $access_token;
				$_SESSION['WPOA']['REFRESH_TOKEN'] = $refresh_token;
				$_SESSION['WPOA']['EXPIRES_IN']    = $expires_in;
				$_SESSION['WPOA']['EXPIRES_AT']    = $expires_at;

				return true;
			}
		}

		/**
		 * Get OAuth identity.
		 *
		 * @return array|mixed|object
		 */
		public function get_oauth_identity() {
			$key_name = isset( $this->config['get_oauth_identity']['access_token'] ) ? $this->config['get_oauth_identity']['access_token'] : 'access_token';

			// here we exchange the access token for the user info...
			// set the access token param.
			$params = array();

			if ( isset( $this->config['get_oauth_identity']['params'] ) ) {
				$params = $this->config['get_oauth_identity']['params'];
			}

			$params[$key_name] = $_SESSION['WPOA']['ACCESS_TOKEN'];

			$url = $this->config['url_user'];

			$result_obj = $this->remote_post( $params, $url );
			$result_obj = json_decode( $result_obj, true );

			// parse and return the user's oauth identity.
			$oauth_identity             = $result_obj;
			$oauth_identity['provider'] = $_SESSION['WPOA']['PROVIDER'];

			return $oauth_identity;
		}
	}
}
