<?php

class Redux_oAuth {
	// Hold the class instance.
	private static $instance = null;
	private $parent = null;
	private $config = array();


	// The constructor is private
	// to prevent initiation with outer code.
	private function __construct( $parent ) {
		// start the user session for maintaining individual user states during the multi-stage authentication flow:
		if ( ! isset( $_SESSION ) ) {
			session_start();
		}
		$this->parent = $parent;
	}

	public function auth_flow() {
		# AUTHENTICATION FLOW #
		// the oauth 2.0 authentication flow will start in this script and make several calls to the third-party authentication provider which in turn will make callbacks to this script that we continue to handle until the login completes with a success or failure:
		if ( ! $this->config['client_enabled'] ) {
			$this->parent->wpoa_end_login( "This third-party authentication provider has not been enabled. Please notify the admin or try again later." );
		} elseif ( ! $this->config['client_id'] || ! $this->config['client_secret'] ) {
			// do not proceed if id or secret is null:
			$this->parent->wpoa_end_login( "This third-party authentication provider has not been configured with an API key/secret. Please notify the admin or try again later." );
		} elseif ( isset( $_GET['error_description'] ) ) {
			// do not proceed if an error was detected:
			$this->parent->wpoa_end_login( $_GET['error_description'] );
		} elseif ( isset( $_GET['error_message'] ) ) {
			// do not proceed if an error was detected:
			$this->parent->wpoa_end_login( $_GET['error_message'] );
		} elseif ( isset( $_GET['code'] ) ) {
			// post-auth phase, verify the state:
			if ( $_SESSION['WPOA']['STATE'] == $_GET['state'] ) {
				// get an access token from the third party provider:
				$this->get_oauth_token();
				// get the user's third-party identity and attempt to login/register a matching wordpress user account:
				$oauth_identity = $this->get_oauth_identity( $this );
				$this->parent->wpoa_login_user( $oauth_identity );
			} else {
				// possible CSRF attack, end the login with a generic message to the user and a detailed message to the admin/logs in case of abuse:
				// TODO: report detailed message to admin/logs here...
				$this->parent->wpoa_end_login( "Sorry, we couldn't log you in. Please notify the admin or try again later." );
			}
		} else {
			// pre-auth, start the auth process:
			if ( ( empty( $_SESSION['WPOA']['EXPIRES_AT'] ) ) || ( time() > $_SESSION['WPOA']['EXPIRES_AT'] ) ) {
				// expired token; clear the state:
				$this->parent->wpoa_clear_login_state();
			}
			$this->get_oauth_code();
		}
		// we shouldn't be here, but just in case...
		$this->parent->wpoa_end_login( "Sorry, we couldn't log you in. The authentication flow terminated in an unexpected way. Please notify the admin or try again later." );
		# END OF AUTHENTICATION FLOW #
	}

	public function set_config( $array ) {
		$this->config = $array;

		// Outside of the scripted config
		$this->config['http_util']       = get_option( 'wpoa_http_util' );
		$this->config['client_enabled']  = get_option( 'wpoa_' . strtolower( $this->config['provider'] ) . '_api_enabled' );
		$this->config['client_id']       = get_option( 'wpoa_' . $this->config['provider'] . '_api_id' );
		$this->config['client_secret']   = get_option( 'wpoa_' . $this->config['provider'] . '_api_secret' );
		$this->config['redirect_uri']    = rtrim( site_url(), '/' ) . '/';
		$this->config['util_verify_ssl'] = get_option( 'wpoa_http_util_verify_ssl' );
		$_SESSION['WPOA']['PROVIDER']    = ucfirst( $this->config['provider'] );
		// remember the user's last url so we can redirect them back to there after the login ends:
		if ( ! $_SESSION['WPOA']['LAST_URL'] ) {
			$_SESSION['WPOA']['LAST_URL'] = strtok( $_SERVER['HTTP_REFERER'], "?" );
		}
	}

	// The object is created from within the class itself
	// only if the class has no instance.
	public static function getInstance( $parent ) {
		if ( self::$instance == null ) {
			self::$instance = new Redux_oAuth( $parent );
		}

		return self::$instance;
	}

	# AUTHENTICATION FLOW HELPER FUNCTIONS #
	function get_oauth_code() {
		$params                    = array(
			'response_type' => 'code',
			'client_id'     => $this->config['client_id'],
			'scope'         => $this->config['scope'],
			'state'         => uniqid( '', true ),
			'redirect_uri'  => $this->config['redirect_uri'],
		);
		$_SESSION['WPOA']['STATE'] = $params['state'];
		$url                       = $this->config['url_auth'] . http_build_query( $params );
		header( "Location: $url" );
		exit;
	}

	function curl( $params, $url, $post=False ) {
		$url_params = http_build_query( $params );
		$url        = $url . $url_params;
		$curl       = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		if ( $post ) {
			curl_setopt( $curl, CURLOPT_POST, 1 );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $params );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, ( $this->config['util_verify_ssl'] == 1 ? 1 : 0 ) );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, ( $this->config['util_verify_ssl'] == 1 ? 2 : 0 ) );
		}
		$result = curl_exec( $curl );

		return $result;
	}

	function steam( $params, $url ) {
		$url_params = http_build_query( $params );
		$url        = rtrim( $url, "?" );
		$opts       = array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $url_params,
			),
		);
		$context    = $context = stream_context_create( $opts );
		$result     = @file_get_contents( $url, false, $context );
		if ( $result === false ) {
			$this->parent->wpoa_end_login( "Sorry, we couldn't log you in. Could not retrieve access token via stream context. Please notify the admin or try again later." );
		}

		return $result;
	}

	function get_oauth_token() {
		$params = array(
			'grant_type'    => 'authorization_code',
			'client_id'     => $this->config['client_id'],
			'client_secret' => $this->config['client_secret'],
			'code'          => $_GET[ $this->config['code'] ],
			'redirect_uri'  => $this->config['redirect_uri'],
		);
		$url    = $this->config['url_token'];

		$result_obj = $this->config['http_util'] == 'curl' ? $this->curl( $params, $url, true ) : $this->stream( $params, $url );

		if ( isset( $this->config['get_oauth_token']['json_decode'] ) && $this->config['get_oauth_token']['json_decode'] === true ) {
			$result_obj = json_decode( $result_obj, true );
		}
		// process the result:
		$access_token  = $result_obj[ $this->config['get_oauth_token']['access_token'] ];
		$expires_in    = $result_obj[ $this->config['get_oauth_token']['expires_in'] ];
		$refresh_token = "";
		if ( isset( $this->config['get_oauth_token']['refresh_token'] ) && isset( $result_obj[ $this->config['get_oauth_token']['refresh_token'] ] ) ) {
			$refresh_token = $result_obj[ $this->config['get_oauth_token']['refresh_token'] ];
		}
		$expires_at = time() + $expires_in;
		// handle the result:
		if ( ! $access_token || ! $expires_in ) {
			// malformed access token result detected:
			$this->parent->wpoa_end_login( "Sorry, we couldn't log you in. Malformed access token result detected. Please notify the admin or try again later." );
		} else {
			$_SESSION['WPOA']['ACCESS_TOKEN']  = $access_token;
			$_SESSION['WPOA']['REFRESH_TOKEN'] = $refresh_token;
			$_SESSION['WPOA']['EXPIRES_IN']    = $expires_in;
			$_SESSION['WPOA']['EXPIRES_AT']    = $expires_at;

			return true;
		}
	}

	function get_oauth_identity() {
		// here we exchange the access token for the user info...
		// set the access token param:
		$params                 = $this->config['get_oauth_identity']['params'];
		$params['access_token'] = $_SESSION['WPOA']['ACCESS_TOKEN'];

		$url        = $this->config['url_user'];
		$result_obj = $this->config['http_util'] == 'curl' ? $this->curl( $params, $url ) : $this->stream( $params, $url );
		$result_obj = json_decode( $result_obj, true );
		// parse and return the user's oauth identity:
		$oauth_identity             = $result_obj;
		$oauth_identity['provider'] = $_SESSION['WPOA']['PROVIDER'];
		if ( isset( $this->config['get_oauth_identity']['id'] ) && isset( $oauth_identity[ $this->config['get_oauth_identity']['id'] ] ) ) {
			if ( $oauth_identity[ $this->config['get_oauth_identity']['id'] ] != "id" ) {
				$oauth_identity['id'] = $oauth_identity[ $this->config['get_oauth_identity']['id'] ];
				unset( $oauth_identity[ $this->config['get_oauth_identity']['id'] ] );
			}
		}
		if ( isset( $this->config['get_oauth_identity']['email'] ) && isset( $oauth_identity[ $this->config['get_oauth_identity']['email'] ] ) ) {
			if ( $oauth_identity[ $this->config['get_oauth_identity']['email'] ] != "email" ) {
				$oauth_identity['id'] = $oauth_identity[ $this->config['get_oauth_identity']['email'] ];
				unset( $oauth_identity[ $this->config['get_oauth_identity']['email'] ] );
			}
		}
	//	print_r($oauth_identity);exit();
		if ( ! $oauth_identity['id'] ) {
			$this->parent->wpoa_end_login( "Sorry, we couldn't log you in. User identity was not found. Please notify the admin or try again later." );
		}

		return $oauth_identity;
	}

}
