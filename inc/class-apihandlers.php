<?php
/**
 * API Handlers class for WP Schedule Plugin.
 */

namespace JohanBeijer\WPSchedule;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles REST API endpoint registration and callbacks.
 */
class ApiHandlers {

	/**
	 * Database handler instance.
	 *
	 * @var Database
	 */
	private $db;

	/**
	 * API Namespace.
	 *
	 * @var string
	 */
	private $namespace = 'wp-schedule-plugin/v1';

	/**
	 * Constructor.
	 *
	 * @param Database $db Database handler instance.
	 */
	public function __construct( Database $db ) {
		$this->db = $db;
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		\register_rest_route(
			$this->namespace,
			'/test',
			[
				'methods'             => \WP_REST_Server::READABLE, // GET
				'callback'            => [ $this, 'handle_test_request' ],
				'permission_callback' => [ $this, 'permission_check_callback' ],
			]
		);

		// --- Routes for Fas 1 (Organizations) will be registered here ---
		// \register_rest_route( $this->namespace, '/organizations', ... );
		// \register_rest_route( $this->namespace, '/organizations/(?P<org_id>\d+)', ... );

		// --- Routes for Fas 2 (Resources) will be registered here ---

		// --- Routes for Fas 3 (Shifts) will be registered here ---

		// --- Routes for Fas 4 (Members) will be registered here ---
	}

	/**
	 * Generic permission callback. Checks if user can access the plugin.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if user has permission, WP_Error otherwise.
	 */
	public function permission_check_callback( \WP_REST_Request $request ): bool|\WP_Error {
		if ( ! \current_user_can( 'access_schemaplugin' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				\__( 'Sorry, you do not have permission to access this endpoint.', 'wp-schedule-plugin' ),
				[ 'status' => \rest_authorization_required_code() ]
			);
		}
		return true;
	}

	/**
	 * Verify nonce from request header or parameter.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	protected function verify_nonce( \WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce ) {
			$nonce = $request->get_param( '_wpnonce' ); // Check query param as fallback
		}

		return \wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Standardized API response helper.
	 *
	 * @param bool   $success     Whether the request was successful.
	 * @param string $message_key Translation key for the message.
	 * @param array  $data        Optional data to include in the response.
	 * @param int    $status_code HTTP status code.
	 * @return \WP_REST_Response
	 */
	protected function api_response( bool $success, string $message_key, array $data = [], int $status_code = 200 ): \WP_REST_Response {
		$response_data = [
			'success' => $success,
			'message' => \__( $message_key, 'wp-schedule-plugin' ),
		];
		if ( ! empty( $data ) ) {
			$response_data['data'] = $data;
		}

		// Adjust status code for errors if not explicitly set otherwise
		if ( ! $success && $status_code === 200 ) {
			$status_code = 400; // Default to Bad Request for errors
		}

		return new \WP_REST_Response( $response_data, $status_code );
	}

	/**
	 * Handle the test API request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function handle_test_request( \WP_REST_Request $request ): \WP_REST_Response {
		// Example of using the helper
		return $this->api_response( true, 'apiTestSuccess', [ 'timestamp' => time() ] );
	}

	// --- Handlers for Fas 1 (Organizations) will go here ---
	// public function get_organizations_handler(...) {}
	// public function create_organization_handler(...) {}
	// ... etc ...

	// --- Handlers for Fas 2 (Resources) will go here ---

	// --- Handlers for Fas 3 (Shifts) will go here ---

	// --- Handlers for Fas 4 (Members) will go here ---

}