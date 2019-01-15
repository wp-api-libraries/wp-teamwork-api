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
 * GitHub Plugin URI: https://github.com/wp-api-libraries/wp-teamwork-ap
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

		// AUTHENTICATE.

		/**
		 * authenticate function.
		 *
		 * @access public
		 * @return void
		 */
		public function authenticate() {
			return $this->build_request( '/authenticate.json' )->fetch();
		}

		// ACCOUNT.

		/**
		 * get_accounts function.
		 *
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_accounts( $args = array() ) {
			return $this->build_request( '/accounts.json' )->fetch();
		}

		// ACTIVITY.

		/**
		 * get_latest_activity function.
		 *
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_latest_activity( $args = array() ) {
			return $this->build_request( '/latestActivity.json' )->fetch();
		}

		/**
		 * get_projects_latest_activity function.
		 *
		 * @access public
		 * @param mixed $project_id
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_projects_latest_activity( $project_id, $args = array() ) {
			return $this->build_request( '/projects/'. $project_id .'/latestActivity.json' )->fetch();
		}

		/**
		 * get_task_activity function.
		 *
		 * @access public
		 * @param mixed $task_id
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_task_activity( $task_id, $args = array() ) {
			return $this->build_request( '/yoursite/tasks/'. $task_id .'/activity.json' )->fetch();
		}

		/**
		 * get_task_audit_history function.
		 *
		 * @access public
		 * @param mixed $task_id
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_task_audit_history( $task_id, $args = array() ) {
			return $this->build_request( '/tasks/'. $task_id .'/audit.json' )->fetch();
		}

		/**
		 * delete_activity function.
		 *
		 * @access public
		 * @param mixed $activity_id
		 * @return void
		 */
		public function delete_activity( $activity_id ) {
			return $this->build_request( '/activity/'. $activity_id .'.json', 'DELETE' )->fetch();
		}

		// PROJECTS.

		/**
		 * Get Projects
		 *
		 * @docs https://developer.teamwork.com/projects/projects/retrieve-all-projects
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_projects( $args = array() ) {
			return $this->build_request( '/projects.json', $args )->fetch();
		}

		/**
		 * get_project function.
		 *
		 * @docs https://developer.teamwork.com/projects/projects/retrieve-a-single-project
		 * @access public
		 * @param mixed $project_id
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_project( $project_id, $args = array() ) {
			return $this->build_request( '/projects/' . $project_id . '.json', $args )->fetch();
		}

		/**
		 * get_company_projects function.
		 *
		 * @access public
		 * @param mixed $company_id
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_company_projects( $company_id, $args = array() ) {
			return $this->build_request( '/companies/' . $company_id . '/projects.json', $args )->fetch();
		}

		/**
		 * get_starred_projects function.
		 *
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_starred_projects( $args = array() ) {
			return $this->build_request( '/projects/starred.json', $args )->fetch();
		}

		/**
		 * create_project function.
		 *
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function create_project( $args = array() ) {
			return $this->build_request( '/projects.json', $args, 'POST' )->fetch();
		}

		/**
		 * set_project_rates function.
		 *
		 * @access public
		 * @param mixed $project_id
		 * @param array $args (default: array())
		 * @return void
		 */
		public function set_project_rates( $project_id, $args = array() ) {
			return $this->build_request( '/projects/'. $project_id .'rates.json', $args, 'POST' )->fetch();
		}

		/**
		 * update_project function.
		 *
		 * @access public
		 * @param mixed $project_id
		 * @param array $args (default: array())
		 * @return void
		 */
		public function update_project( $project_id, $args = array() ) {
			return $this->build_request( '/projects/'. $project_id .'.json', $args, 'PUT' )->fetch();
		}

		/**
		 * star_project function.
		 *
		 * @access public
		 * @param mixed $project_id
		 * @return void
		 */
		public function star_project( $project_id ) {
			return $this->build_request( '/projects/'. $project_id .'/star.json', 'PUT' )->fetch();
		}

		/**
		 * unstar_project function.
		 *
		 * @access public
		 * @param mixed $project_id
		 * @return void
		 */
		public function unstar_project( $project_id ) {
			return $this->build_request( '/projects/'. $project_id .'/unstar.json', 'PUT' )->fetch();
		}

		/**
		 * delete_project function.
		 *
		 * @access public
		 * @param mixed $project_id
		 * @return void
		 */
		public function delete_project( $project_id ) {
			return $this->build_request( '/projects/'. $project_id .'/unstar.json', 'DELETE' )->fetch();
		}

		// PROJECT CATEGORIES.

		/**
		 * get_project_categories function.
		 *
		 * @access public
		 * @param mixed $args
		 * @return void
		 */
		public function get_project_categories( $args ) {
			return $this->build_request( '/projectsCategories.json', $args )->fetch();
		}

		/**
		 * get_project_category_by_id function.
		 *
		 * @access public
		 * @param mixed $project_category_id
		 * @return void
		 */
		public function get_project_category_by_id( $project_category_id ) {
			return $this->build_request( '/projectsCategories/'. $project_category_id .'.json' )->fetch();
		}

		/**
		 * get_tasks_by_project_categories function.
		 *
		 * @access public
		 * @param mixed $project_category_id
		 * @return void
		 */
		public function get_tasks_by_project_categories( $project_category_id ) {
			return $this->build_request( '/projectsCategories/'. $project_category_id .'/tasks.json' )->fetch();
		}

		/**
		 * add_project_category function.
		 *
		 * @access public
		 * @param mixed $args
		 * @return void
		 */
		public function add_project_category( $args ) {
			return $this->build_request( '/projectsCategories.json', $args, 'POST' )->fetch();
		}

		/**
		 * update_project_category function.
		 *
		 * @access public
		 * @param mixed $project_category_id
		 * @param mixed $args
		 * @return void
		 */
		public function update_project_category( $project_category_id, $args ) {
			return $this->build_request( '/projectsCategories/'. $project_category_id .'.json', $args, 'PUT' )->fetch();
		}

		/**
		 * delete_project_category function.
		 *
		 * @access public
		 * @param mixed $project_category_id
		 * @return void
		 */
		public function delete_project_category( $project_category_id ) {
			return $this->build_request( '/projectsCategories/'. $project_category_id .'.json', 'DELETE' )->fetch();
		}

		// PROJECT OWNER.

		// SITE OWNER.

		// MILESTONES.

		/**
		 * get_milestones function.
		 *
		 * @access public
		 * @param mixed $args
		 * @return void
		 */
		public function get_milestones( $args ) {
			return $this->build_request( '/milestones.json' )->fetch();
		}

		/**
		 * get_projects_milestones function.
		 *
		 * @access public
		 * @param mixed $project_id
		 * @param mixed $args
		 * @return void
		 */
		public function get_projects_milestones( $project_id, $args = array() ) {
			return $this->build_request( '/projects/'. $project_id .'/milestones.json' )->fetch();
		}

		// TASK LISTS.

		// TASKS.

		public function get_tasks( $args ) {

		}

		public function get_projects_tasks( $project_id, $args = array() ) {
			return $this->build_request( '/projects/'. $project_id .'/tasks.json' )->fetch();
		}

		// TASK REMINDERS.


		// COMPANIES.

		/**
		 * get_companies function.
		 *
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_companies( $args = array() ) {
			return $this->build_request( '/companies.json', $args )->fetch();
		}


		/**
		 * get_project_companies function.
		 *
		 * @access public
		 * @param mixed $project_id
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_project_companies( $project_id, $args = array() ) {
			return $this->build_request( '/projects/'. $project_id.'/companies.json', $args )->fetch();
		}

		/**
		 * get_company function.
		 *
		 * @access public
		 * @param mixed $company_id
		 * @return void
		 */
		public function get_company( $company_id ) {
			return $this->build_request( '/companies/'. $company_id .'.json' )->fetch();
		}

		/**
		 * create_company function.
		 *
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function create_company( $args = array() ) {
			return $this->build_request( '/companies.json', $args, 'POST' )->fetch();
		}

		/**
		 * update_company function.
		 *
		 * @access public
		 * @param mixed $company_id
		 * @param array $args (default: array())
		 * @return void
		 */
		public function update_company( $company_id, $args = array() ) {
			return $this->build_request( '/companies/'. $company_id .'.json', $args, 'PUT' )->fetch();
		}

		/**
		 * delete_company function.
		 *
		 * @access public
		 * @param mixed $company_id
		 * @return void
		 */
		public function delete_company( $company_id ) {
			return $this->build_request( '/companies/'. $company_id .'.json', 'DELETE' )->fetch();
		}

		// PEOPLE.

		/**
		 * get_people function.
		 *
		 * @access public
		 * @param mixed $args
		 * @return void
		 */
		public function get_people( $args = array() ) {
			return $this->build_request( '/people.json', $args )->fetch();
		}

		/**
		 * get_people_available_for_calendar_event function.
		 *
		 * @access public
		 * @param mixed $args
		 * @return void
		 */
		public function get_people_available_for_calendar_event( $args = array() ) {

		}

		public function get_people_avail_for_message( $args = array() ) {

		}

		/**
		 * add_user function.
		 *
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function add_user( $args = array() ) {
			return $this->build_request( '/people.json', $args, 'POST' )->fetch();
		}

		// PEOPLE STATUS.

		// CALENDAR EVENT.

		// FILES.

		// FILE CATEGORIES.

		// NOTEBOOKS.

		// NOTEBOOK CATEGORIES.

		// LINKS.

		// LINK CATEGORIES.

		// CLOCK IN/ CLOCK OUT.

		// TIME TRACKING.

		/**
		 * get_time_entries function.
		 *
		 * @access public
		 * @param mixed $args
		 * @return void
		 */
		public function get_time_entries( $args ) {
			return $this->build_request( '/time_entries.json', $args )->fetch();
		}

		// MESSAGES.

		// MESSAGE REPLIES.

		// MESSAGE CATEGORIES.

		// COMMENTS.

		// INVOICES.

		// EXPENSES.

		// RISKS.

		// BOARDS.

		// PORTFOLIO BOARDS.

		// FILE UPLOADING.

		// PROJECT UPDATES.

		// PROJECT ROLES.

		// PERMISSIONS.

		// LIKES.

		// TAGS.

		// PROJECT EMAIL ADDRESSES.

		// SEARCH.

		// WEBHOOKS.

		// WORKLOADS.

		/**
		 * get_workload function.
		 *
		 * @access public
		 * @param mixed $args
		 * @return void
		 */
		public function get_workload( $args = array() ) {
			return $this->build_request( '/workload.json', $args )->fetch();
		}

		// TRASHCANS.

		/**
		 * get_project_trash function.
		 *
		 * @access public
		 * @param mixed $project_id
		 * @return void
		 */
		public function get_project_trash( $project_id ) {
			return $this->build_request( '/trashcan/projects/'. $project_id .'.json' )->fetch();
		}

		/**
		 * restore_item_from_trash function.
		 *
		 * @access public
		 * @param mixed $resource
		 * @param mixed $resource_id
		 * @return void
		 */
		public function restore_item_from_trash( $resource, $resource_id ) {
			return $this->build_request( '/trashcan/'.$resource.'/'. $resource_id .'/restore.json' )->fetch();
		}

		// TIMEZONES.

		/**
		 * get_timezones function.
		 *
		 * @access public
		 * @param array $args (default: array())
		 * @return void
		 */
		public function get_timezones( $args = array() ) {
			return $this->build_request( '/timezones.json', $args )->fetch();
		}
	}
}
