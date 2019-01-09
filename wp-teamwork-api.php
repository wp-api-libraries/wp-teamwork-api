<?php
/**
 * Library for accessing the Teamwork API on WordPress
 *
 * @package WP-API-Libraries\WP-Teamwork-API
 */
/*
 * Plugin Name: WP Teamwork API
 * Plugin URI: https://wp-api-libraries.com/
 * Description: Perform API requests.
 * Author: WP API Libraries
 * Version: 1.0.0
 * Author URI: https://wp-api-libraries.com
 * GitHub Plugin URI: https://github.com/imforza
 * GitHub Branch: master
 */
 // Exit if accessed directly.
 defined( 'ABSPATH' ) || exit;
 
if ( ! class_exists( 'TeamworkAPI' ) ) {

	/**
	 * TeamworkAPI class.
	 */
	class TeamworkAPI {
		
		/**
		 * Basic auth username.
		 *
		 * @var string
		 */
		protected $username;
		
		/**
		 * Basic auth password.
		 *
		 * @var string
		 */
		protected $password;
		
		/**
		 * BaseAPI Endpoint
		 *
		 * @var string
		 * @access protected
		 */
		protected $base_uri;
		
		/**
		 * Route being called.
		 *
		 * @var string
		 */
		protected $route = '';
		
		
		/**
		 * Class constructor..
		 * 
		 * @access public
		 * @param mixed $base_uri Base URI.
		 * @param mixed $username Username.
		 * @param mixed $password Password.
		 * @return void
		 */
		public function __construct( $base_uri, $username, $password ) {
			$this->base_uri = $base_uri;
			$this->username = $username;
			$this->password = $password;
		}
		/**
		 * Prepares API request.
		 *
		 * @param  string $route   API route to make the call to.
		 * @param  array  $args    Arguments to pass into the API call.
		 * @param  array  $method  HTTP Method to use for request.
		 * @return self            Returns an instance of itself so it can be chained to the fetch method.
		 */
		protected function build_request( $route, $args = array(), $method = 'GET' ) {
			// Start building query.
			$this->set_headers();
			$this->args['method'] = $method;
			$this->route          = $route;
			// Generate query string for GET requests.
			if ( 'GET' === $method ) {
				$this->route = add_query_arg( array_filter( $args ), $route );
			} elseif ( 'application/json' === $this->args['headers']['Content-Type'] ) {
				$this->args['body'] = wp_json_encode( $args );
			} else {
				$this->args['body'] = $args;
			}
			$this->args['timeout'] = 20;
			return $this;
		}
		/**
		 * Fetch the request from the API.
		 *
		 * @access private
		 * @return array|WP_Error Request results or WP_Error on request failure.
		 */
		protected function fetch() {
			
			// Make the request.
			$response = wp_remote_request( $this->base_uri . $this->route, $this->args );
			
			// Retrieve Status code & body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );
			$this->clear();
			
			// Return WP_Error if request is not successful.
			if ( ! $this->is_status_ok( $code ) ) {
				return new WP_Error( 'response-error', sprintf( __( 'Status: %d', 'wp-teamwork-api' ), $code ), $body );
			}
			return $body;
		}
		/**
		 * Set request headers.
		 */
		protected function set_headers() {

			$this->args['headers'] = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( "{$this->username}:{$this->password}" ),
			);
		}
		
		/**
		 * Clear query data.
		 */
		protected function clear() {
			$this->args       = array();
			$this->query_args = array();
		}
		
		/**
		 * Check if HTTP status code is a success.
		 *
		 * @param  int $code HTTP status code.
		 * @return boolean       True if status is within valid range.
		 */
		protected function is_status_ok( $code ) {
			return ( 200 <= $code && 300 > $code );
		}

		// PROJECTS 
		
		
		/**
		 * Get Projects
		 * 
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_projects( $args = array() ) {
			return $this->build_request( '/projects.json', $args )->fetch();
		}


	}
}
