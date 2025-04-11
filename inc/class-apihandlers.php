<?php
/**
 * API Handlers class for WP Schedule Plugin.
 */

namespace JohanBeijer\WPSchedule;

// WordPress classes used in this file
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use WP_REST_Response; // Added for type hinting
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
		\error_log('WP Schedule Plugin: ApiHandlers->register_routes() called.'); // DEBUG

		// --- Test Route ---
		\register_rest_route(
			$this->namespace,
			'/test',
			[
				'methods'             => WP_REST_Server::READABLE, // GET
				'callback'            => [ $this, 'handle_test_request' ],
				'permission_callback' => [ $this, 'permission_check_callback' ],
			]
		);
		\error_log('WP Schedule Plugin: /test route registered.'); // DEBUG

		// --- Organization Routes (Fas 1) ---
		\register_rest_route(
			$this->namespace,
			'/organizations', // Collection route
			[
				// POST /organizations (Create)
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_organization_handler' ],
					'permission_callback' => [ $this, 'permission_check_manage_options' ],
					'args'                => $this->get_organization_args( false ),
				],
				// GET /organizations (List)
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_organizations_handler' ],
					'permission_callback' => [ $this, 'permission_check_callback' ],
				],
			]
		);

		\register_rest_route(
			$this->namespace,
			'/organizations/(?P<org_id>\d+)', // Item route
			[
				// GET /organizations/{id} (Single)
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_organization_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$org_id_or_error = $this->get_validated_org_id( $request, 'employee' );
						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => [ 'org_id' => $this->get_org_id_arg_schema() ],
				],
				// PUT /organizations/{id} (Update)
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_organization_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						if ( \current_user_can( 'manage_options' ) ) return true;
						$org_id_or_error = $this->get_validated_org_id( $request, 'org_admin' );
						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => $this->get_organization_args( true ),
				],
				// DELETE /organizations/{id} (Delete)
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_organization_handler' ],
					'permission_callback' => [ $this, 'permission_check_manage_options' ],
					'args'                => [ 'org_id' => $this->get_org_id_arg_schema() ],
				],
			]
		);

		// --- Dashboard Stats Route ---
		\register_rest_route(
			$this->namespace,
			'/dashboard-stats',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'handle_dashboard_stats' ],
					'permission_callback' => [ $this, 'permission_check_callback' ],
				],
			]
		);

		// --- Shifts Summary Route ---
		\register_rest_route(
			$this->namespace,
			'/shifts/summary',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'handle_shifts_summary' ],
					'permission_callback' => [ $this, 'permission_check_callback' ],
					'args'                => [
						'group_by' => [
							'type'    => 'string',
							'enum'    => [ 'week' ],
							'default' => 'week',
							'description' => 'Group shifts by week',
							'required' => false,
						],
					],
				],
			]
		);


		// --- Member Routes (Fas 1 / Fas 4) ---
		\register_rest_route(
			$this->namespace,
			'/organization_members', // Collection route for members
			[
				// GET /organization_members (List members for an org)
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_organization_members_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$org_id_or_error = $this->get_validated_org_id( $request, 'employee', 'org_id' );
						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => $this->get_member_list_args(),
				],
				// POST /organization_members (Add member)
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_organization_member_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$org_id_or_error = $this->get_validated_org_id( $request, 'scheduler', 'org_id' );
						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => $this->get_member_args( false ),
				],
			]
		);

		\register_rest_route(
			$this->namespace,
			'/organization_members/(?P<user_id>\d+)', // Item route for members (by user ID)
			[
				// PUT /organization_members/{user_id} (Update Member)
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_organization_member_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$org_id_or_error = $this->get_validated_org_id( $request, 'scheduler', 'org_id' );
						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => $this->get_member_args( true ),
				],
				// DELETE /organization_members/{user_id} (Remove Member)
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_organization_member_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$org_id_or_error = $this->get_validated_org_id( $request, 'org_admin', 'org_id' );
						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => [
						'user_id' => $this->get_user_id_arg_schema(),
						'org_id'  => $this->get_org_id_arg_schema( true ), // Required in query for permission check
					],
				],
			]
		);

		// --- Resource Routes (Fas 2) ---
		// Register POST /resources (Create)
		\register_rest_route(
			$this->namespace,
			'/resources', // Collection route
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_resource_handler' ],
				'permission_callback' => function( WP_REST_Request $request ) {
					// Validate org_id from body and require 'scheduler' role
					$org_id_or_error = $this->get_validated_org_id( $request, 'scheduler', 'org_id' );
					return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
				},
				'args'                => $this->get_resource_args( false ),
			]
		);

		// Register GET /resources (List) separately
		\register_rest_route(
			$this->namespace,
			'/resources', // Same route path
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_resources_handler' ],
				'permission_callback' => function( WP_REST_Request $request ) { // Use proper permission check
					$org_id_or_error = $this->get_validated_org_id( $request, 'employee', 'org_id' );
					return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
				},
				'args'                => $this->get_resource_list_args(),
			]
		);

		// Register Item routes /resources/{resource_id} (GET, PUT, DELETE)
		\register_rest_route(
			$this->namespace,
			'/resources/(?P<resource_id>\d+)', // Item route
			[
				// GET /resources/{id} (Single)
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_resource_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$resource_id = \absint( $request['resource_id'] );
						if ( $resource_id <= 0 ) {
							return new WP_Error( 'rest_invalid_param', \__( 'Invalid resource ID provided.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
						}

						$resource = $this->db->get_resource( $resource_id );
						if ( ! $resource || \is_wp_error( $resource ) ) {
							return new WP_Error( 'rest_not_found', \__( 'Resource not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
						}

						// Now check permission based on the resource's org_id
						$user_id = \get_current_user_id();
						if ( $user_id <= 0 ) {
							return new WP_Error( 'rest_not_logged_in', \__( 'You must be logged in.', 'wp-schedule-plugin' ), [ 'status' => 401 ] );
						}
						if ( \current_user_can( 'manage_options' ) ) {
							return true; // Site admin can access any resource
						}

						$user_role = $this->db->get_user_internal_role( $user_id, $resource->org_id );
						if ( $user_role === false ) {
							return new WP_Error( 'rest_forbidden_member', \__( 'You are not a member of the organization this resource belongs to.', 'wp-schedule-plugin' ), [ 'status' => 403 ] );
						}

						// Check if user has at least 'employee' role
						$min_role = 'employee';
						$role_hierarchy = [ 'employee' => 1, 'scheduler' => 2, 'org_admin' => 3 ];
						if ( ! isset( $role_hierarchy[ $user_role ] ) || ! isset( $role_hierarchy[ $min_role ] ) || $role_hierarchy[ $user_role ] < $role_hierarchy[ $min_role ] ) {
							return new WP_Error( 'rest_forbidden_role', \sprintf( \__( 'Your role ("%s") does not meet the required level ("%s") to access this resource.', 'wp-schedule-plugin' ), $user_role, $min_role ), [ 'status' => 403 ] );
						}

						return true; // Permission granted
					},
					'args'                => [
						'resource_id' => [
							'description'       => \__( 'Unique identifier for the resource.', 'wp-schedule-plugin' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
							'sanitize_callback' => '\absint',
						],
					],
				],
				// PUT /resources/{id} (Update)
				[
					'methods'             => WP_REST_Server::EDITABLE, // PUT/PATCH
					'callback'            => [ $this, 'update_resource_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$resource_id = \absint( $request['resource_id'] );
						if ( $resource_id <= 0 ) {
							return new WP_Error( 'rest_invalid_param', \__( 'Invalid resource ID provided.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
						}

						$resource = $this->db->get_resource( $resource_id );
						if ( ! $resource || \is_wp_error( $resource ) ) {
							return new WP_Error( 'rest_not_found', \__( 'Resource not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
						}

						// Use get_validated_org_id to check 'scheduler' role for the resource's org
						$request->set_param( 'org_id', $resource->org_id );
						$org_id_or_error = $this->get_validated_org_id( $request, 'scheduler', 'org_id' );
						$request->set_param( 'org_id', null ); // Clean up

						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => $this->get_resource_args( true ), // Use true for update context
				],
				// DELETE /resources/{id} (Delete)
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_resource_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$resource_id = \absint( $request['resource_id'] );
						if ( $resource_id <= 0 ) {
							return new WP_Error( 'rest_invalid_param', \__( 'Invalid resource ID.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
						}

						$resource = $this->db->get_resource( $resource_id );
						if ( ! $resource || \is_wp_error( $resource ) ) {
							return new WP_Error( 'rest_not_found', \__( 'Resource not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
						}

						if ( \current_user_can( 'manage_options' ) ) {
							return true;
						}

						$user_id = \get_current_user_id();
						if ( $user_id <= 0 ) {
							return new WP_Error( 'rest_not_logged_in', \__( 'You must be logged in.', 'wp-schedule-plugin' ), [ 'status' => 401 ] );
						}

						$user_role = $this->db->get_user_internal_role( $user_id, $resource->org_id );
						if ( $user_role !== 'org_admin' ) {
							return new WP_Error( 'rest_forbidden_role', \__( 'You must be an organization administrator to delete this resource.', 'wp-schedule-plugin' ), [ 'status' => 403 ] );
						}

						return true; // Permission granted
					},
					'args'                => [
						'resource_id' => [
							'required'          => true,
							'type'              => 'integer',
							'description'       => \__( 'Unique identifier for the resource.', 'wp-schedule-plugin' ),
							'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
							'sanitize_callback' => '\absint',
						],
					],
				],
			] // Close the main array of route definitions
		);


		// --- Routes for Fas 3 (Shifts) will be registered here ---
		\register_rest_route(
			$this->namespace,
			'/shifts', // Collection route
			[
				// POST /shifts (Create)
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_shift_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						// Validate org_id from body and require 'scheduler' role
						$org_id_or_error = $this->get_validated_org_id( $request, 'scheduler', 'org_id' );
						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => $this->get_shift_args(),
				],
				// GET /shifts (List)
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_shifts_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						// Validate org_id from query params and require 'employee' role
						$org_id_or_error = $this->get_validated_org_id( $request, 'employee', 'org_id' );
						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => $this->get_shift_list_args(),
				],
				// PUT /shifts/{id} (Update) - To be implemented later
				// DELETE /shifts/{id} (Delete) - To be implemented later
			]
		);

		// NEW: Register Item route /shifts/{shift_id} (GET, PUT, DELETE)
		\register_rest_route(
			$this->namespace,
			'/shifts/(?P<shift_id>\d+)', // Item route
			[
				// GET /shifts/{id} (Single)
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_shift_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$shift_id = \absint( $request['shift_id'] );
						if ( $shift_id <= 0 ) {
							return new WP_Error( 'rest_invalid_param', \__( 'Invalid shift ID provided.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
						}

						$shift = $this->db->get_shift( $shift_id );
						if ( ! $shift || \is_wp_error( $shift ) ) {
							return new WP_Error( 'rest_not_found', \__( 'Shift not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
						}

						// Use get_validated_org_id to check 'employee' role for the shift's org
						// Temporarily set 'org_id' in the request for validation function
						$request->set_param( 'org_id', $shift->org_id );
						$org_id_or_error = $this->get_validated_org_id( $request, 'employee', 'org_id' );
						$request->set_param( 'org_id', null ); // Clean up

						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => [
						'shift_id' => [
							'description'       => \__( 'Unique identifier for the shift.', 'wp-schedule-plugin' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
							'sanitize_callback' => '\absint',
						],
					],
				],
				// NEW: PUT /shifts/{id} (Update)
				[
					'methods'             => \WP_REST_Server::EDITABLE, // PUT/PATCH
					'callback'            => [ $this, 'update_shift_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$shift_id = \absint( $request['shift_id'] );
						if ( $shift_id <= 0 ) {
							return new WP_Error( 'rest_invalid_param', \__( 'Invalid shift ID provided.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
						}

						$shift = $this->db->get_shift( $shift_id );
						if ( ! $shift || \is_wp_error( $shift ) ) {
							return new WP_Error( 'rest_not_found', \__( 'Shift not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
						}

						// Use get_validated_org_id to check 'scheduler' role for the shift's org
						// Temporarily set 'org_id' in the request for validation function
						$request->set_param( 'org_id', $shift->org_id );
						$org_id_or_error = $this->get_validated_org_id( $request, 'scheduler', 'org_id' );
						$request->set_param( 'org_id', null ); // Clean up

						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => $this->get_shift_update_args(),
				],
				// NEW: DELETE /shifts/{id} (Delete)
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_shift_handler' ],
					'permission_callback' => function( WP_REST_Request $request ) {
						$shift_id = \absint( $request['shift_id'] );
						if ( $shift_id <= 0 ) {
							return new WP_Error( 'rest_invalid_param', \__( 'Invalid shift ID provided.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
						}

						$shift = $this->db->get_shift( $shift_id );
						if ( ! $shift || \is_wp_error( $shift ) ) {
							return new WP_Error( 'rest_not_found', \__( 'Shift not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
						}

						// Use get_validated_org_id to check 'scheduler' role for the shift's org
						$request->set_param( 'org_id', $shift->org_id );
						$org_id_or_error = $this->get_validated_org_id( $request, 'scheduler', 'org_id' );
						$request->set_param( 'org_id', null ); // Clean up

						return $org_id_or_error instanceof WP_Error ? $org_id_or_error : true;
					},
					'args'                => [
						'shift_id' => [
							'description'       => \__( 'Unique identifier for the shift.', 'wp-schedule-plugin' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
							'sanitize_callback' => '\absint',
						],
					],
				],
			]
		);

	} // End of register_routes() method


	// --- Argument Schemas ---

	private function get_org_id_arg_schema( bool $is_query_param = false ): array {
		return [
			'description'       => \__( 'Unique identifier for the organization.', 'wp-schedule-plugin' ),
			'type'              => 'integer',
			'required'          => $is_query_param,
			'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
			'sanitize_callback' => '\absint',
		];
	}

	private function get_user_id_arg_schema(): array {
		return [
			'description'       => \__( 'Unique identifier for the user.', 'wp-schedule-plugin' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
			'sanitize_callback' => '\absint',
		];
	}

	private function get_organization_args( bool $is_update = false ): array {
		$args = [
			'name' => [
				'required'          => ! $is_update,
				'type'              => 'string',
				'description'       => \__( 'Name of the organization.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\sanitize_text_field',
				'validate_callback' => function( $param ) { return ! empty( \trim( $param ) ); },
			],
			'parent_org_id' => [
				'required'          => false,
				'type'              => ['integer', 'null'],
				'description'       => \__( 'ID of the parent organization (or null/0 for root).', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) { return $param === null || ( \is_numeric( $param ) && $param >= 0 ); },
				'sanitize_callback' => function( $value ) { return \is_null( $value ) ? null : \absint( $value ); },
			],
		];
		if ( $is_update ) {
			$args['org_id'] = $this->get_org_id_arg_schema(); // org_id from URL path
		}
		return $args;
	}

	private function get_member_list_args(): array {
		return [
			'org_id' => $this->get_org_id_arg_schema( true ), // Required query param
			'search' => [
				'type'              => 'string',
				'description'       => \__( 'Search term for member name or email.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\sanitize_text_field',
			],
			'number' => [
				'type'              => 'integer',
				'description'       => \__( 'Number of items per page.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\absint',
				'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param >= 0; },
				'default'           => 20,
			],
			'offset' => [
				'type'              => 'integer',
				'description'       => \__( 'Offset for pagination.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\absint',
				'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param >= 0; },
				'default'           => 0,
			],
		];
	}

	private function get_member_args( bool $is_update = false ): array {
		$args = [
			'org_id' => $this->get_org_id_arg_schema( true ), // Required query param for permission check
			'user_id' => $this->get_user_id_arg_schema(), // Required in body for create
			'internal_role' => [
				'required'          => ! $is_update,
				'type'              => 'string',
				'description'       => \__( 'Internal role for the member.', 'wp-schedule-plugin' ),
				'enum'              => [ 'employee', 'scheduler', 'org_admin' ],
			],
			'employment_number' => [
				'required'          => false,
				'type'              => ['string', 'null'],
				'description'       => \__( 'Optional employment number.', 'wp-schedule-plugin' ),
				'sanitize_callback' => function( $value ) {
					return \is_null( $value ) ? null : \sanitize_text_field( $value );
				},
			],
		];
		if ( $is_update ) {
			$args['internal_role']['required'] = false;
			// user_id comes from URL in update, not body
			unset($args['user_id']);
		}
		return $args;
	}

	private function get_resource_args( bool $is_update = false ): array {
		$args = [
			'org_id' => $this->get_org_id_arg_schema( true ), // Required in body for create/permission check
			'name' => [
				'required'          => ! $is_update,
				'type'              => 'string',
				'description'       => \__( 'Name of the resource.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\sanitize_text_field',
				'validate_callback' => function( $param ) { return ! empty( \trim( $param ) ); },
			],
			'description' => [
				'required'          => false,
				'type'              => ['string', 'null'],
				'description'       => \__( 'Optional description for the resource.', 'wp-schedule-plugin' ),
				'sanitize_callback' => function( $value ) {
					return \is_null( $value ) ? null : \sanitize_textarea_field( $value );
				},
			],
			'type' => [
				'required'          => false,
				'type'              => ['string', 'null'],
				'description'       => \__( 'Optional type classification for the resource.', 'wp-schedule-plugin' ),
				'sanitize_callback' => function( $value ) {
					return \is_null( $value ) ? null : \sanitize_text_field( $value );
				},
			],
			'capacity' => [
				'required'          => false,
				'type'              => ['integer', 'null'],
				'description'       => \__( 'Optional capacity limit for the resource.', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) { return $param === null || ( \is_numeric( $param ) && $param >= 0 ); },
				'sanitize_callback' => function( $value ) {
					return \is_null( $value ) ? null : \absint( $value );
				},
			],
			'is_active' => [
				'required'          => false,
				'type'              => 'boolean',
				'description'       => \__( 'Whether the resource is active (default true).', 'wp-schedule-plugin' ),
				'default'           => true,
				'sanitize_callback' => '\rest_sanitize_boolean',
			],
		];
		if ( $is_update ) {
			// For PUT/PATCH, resource_id comes from the URL, defined in register_rest_route
			$args['resource_id'] = [
				'required'          => true, // Technically from URL, but good to define
				'type'              => 'integer',
				'description'       => \__( 'Unique identifier for the resource (from URL).', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
				'sanitize_callback' => '\absint',
			];
			// Make all body parameters optional for update
			$args['org_id']['required'] = false; // Not required in body (used for permission check based on existing resource)
			$args['name']['required'] = false;
			$args['description']['required'] = false;
			$args['type']['required'] = false;
			$args['capacity']['required'] = false;
			$args['is_active']['required'] = false;
			unset($args['is_active']['default']); // Don't force default on update if not provided
		} else {
			// Ensure org_id and name are required in the body for create
			$args['org_id']['required'] = true;
			$args['name']['required'] = true;
		}
		return $args;
	}

	private function get_resource_list_args(): array {
		return [
			'org_id' => $this->get_org_id_arg_schema( true ), // Required query param
			'type' => [
				'type'              => 'string',
				'description'       => \__( 'Filter resources by type.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\sanitize_text_field',
			],
			'is_active' => [
				'type'              => 'boolean',
				'description'       => \__( 'Filter resources by active status.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\rest_sanitize_boolean',
			],
			'number' => [
				'type'              => 'integer',
				'description'       => \__( 'Number of items per page.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\absint',
				'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param >= 0; },
				'default'           => 20, // Or maybe a different default?
			],
			'offset' => [
				'type'              => 'integer',
				'description'       => \__( 'Offset for pagination.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\absint',
				'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param >= 0; },
				'default'           => 0,
			],
		];
	}

	private function get_shift_args(): array {
		$allowed_statuses = [ 'pending', 'confirmed', 'cancelled', 'draft' ]; // Define allowed statuses

		return [
			'org_id' => $this->get_org_id_arg_schema( true ), // Required in body for permission check & creation
			'start_time' => [
				'required'          => true,
				'type'              => 'string',
				'format'            => 'date-time', // Indicate ISO 8601 format
				'description'       => \__( 'Start time of the shift (ISO 8601 format).', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) {
					return \rest_parse_date( $param ) !== false;
				},
				'sanitize_callback' => function( $param ) {
					// Ensure consistent format, e.g., UTC 'Y-m-d H:i:s' for DB storage
					$timestamp = \rest_parse_date( $param );
					return $timestamp ? \gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
				},
			],
			'end_time' => [
				'required'          => true,
				'type'              => 'string',
				'format'            => 'date-time', // Indicate ISO 8601 format
				'description'       => \__( 'End time of the shift (ISO 8601 format). Must be after start_time.', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param, $request, $key ) {
					$start_timestamp = \rest_parse_date( $request['start_time'] );
					$end_timestamp = \rest_parse_date( $param );
					if ( $end_timestamp === false ) return false; // Invalid date format
					if ( $start_timestamp === false ) return true; // Start time is invalid, let its validation handle it
					return $end_timestamp > $start_timestamp;
				},
				'sanitize_callback' => function( $param ) {
					// Ensure consistent format, e.g., UTC 'Y-m-d H:i:s' for DB storage
					$timestamp = \rest_parse_date( $param );
					return $timestamp ? \gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
				},
			],
			'resource_id' => [
				'required'          => false,
				'type'              => ['integer', 'null'],
				'description'       => \__( 'Optional ID of the assigned resource.', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) { return $param === null || ( \is_numeric( $param ) && $param > 0 ); },
				'sanitize_callback' => function( $value ) { return \is_null( $value ) ? null : \absint( $value ); },
			],
			'user_id' => [
				'required'          => false,
				'type'              => ['integer', 'null'],
				'description'       => \__( 'Optional ID of the assigned user.', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) { return $param === null || ( \is_numeric( $param ) && $param > 0 ); },
				'sanitize_callback' => function( $value ) { return \is_null( $value ) ? null : \absint( $value ); },
			],
			'title' => [
				'required'          => false,
				'type'              => ['string', 'null'],
				'description'       => \__( 'Optional title for the shift.', 'wp-schedule-plugin' ),
				'sanitize_callback' => function( $value ) { return \is_null( $value ) ? null : \sanitize_text_field( $value ); },
			],
			'notes' => [
				'required'          => false,
				'type'              => ['string', 'null'],
				'description'       => \__( 'Optional notes for the shift.', 'wp-schedule-plugin' ),
				'sanitize_callback' => function( $value ) { return \is_null( $value ) ? null : \sanitize_textarea_field( $value ); },
			],
			'status' => [
				'required'          => false,
				'type'              => ['string', 'null'],
				'description'       => \__( 'Optional status of the shift.', 'wp-schedule-plugin' ),
				'enum'              => $allowed_statuses,
				'default'           => 'pending', // Default status if not provided
				'sanitize_callback' => function( $value ) use ( $allowed_statuses ) {
					return \in_array( $value, $allowed_statuses, true ) ? $value : 'pending';
				},
			],
		];
	}

	// NEW: Helper method for GET /shifts query parameters
	private function get_shift_list_args(): array {
		$allowed_statuses = [ 'pending', 'confirmed', 'cancelled', 'draft' ]; // Reuse or define consistently

		return [
			'org_id' => $this->get_org_id_arg_schema( true ), // Required query param for permission check
			'resource_id' => [
				'type'              => 'integer',
				'description'       => \__( 'Filter shifts by assigned resource ID.', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
				'sanitize_callback' => '\absint',
			],
			'user_id' => [
				'type'              => 'integer',
				'description'       => \__( 'Filter shifts by assigned user ID.', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
				'sanitize_callback' => '\absint',
			],
			'status' => [
				'type'              => 'string',
				'description'       => \__( 'Filter shifts by status.', 'wp-schedule-plugin' ),
				'enum'              => $allowed_statuses,
				'sanitize_callback' => function( $value ) use ( $allowed_statuses ) {
					return \in_array( $value, $allowed_statuses, true ) ? $value : null; // Return null if invalid
				},
			],
			'start_date' => [
				'type'              => 'string',
				'format'            => 'date', // YYYY-MM-DD
				'description'       => \__( 'Filter shifts starting on or after this date (YYYY-MM-DD).', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) {
					// Basic validation for YYYY-MM-DD format
					if ( ! \preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param ) ) return false;
					$d = \DateTime::createFromFormat('Y-m-d', $param);
					return $d && $d->format('Y-m-d') === $param;
				},
				'sanitize_callback' => 'sanitize_text_field', // Simple sanitization is okay here
			],
			'end_date' => [
				'type'              => 'string',
				'format'            => 'date', // YYYY-MM-DD
				'description'       => \__( 'Filter shifts ending on or before this date (YYYY-MM-DD).', 'wp-schedule-plugin' ),
				'validate_callback' => function( $param ) {
					// Basic validation for YYYY-MM-DD format
					if ( ! \preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param ) ) return false;
					$d = \DateTime::createFromFormat('Y-m-d', $param);
					return $d && $d->format('Y-m-d') === $param;
				},
				'sanitize_callback' => 'sanitize_text_field',
			],
			'number' => [
				'type'              => 'integer',
				'description'       => \__( 'Number of items per page.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\absint',
				'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param >= 0; },
				'default'           => 50, // Default number of shifts
			],
			'offset' => [
				'type'              => 'integer',
				'description'       => \__( 'Offset for pagination.', 'wp-schedule-plugin' ),
				'sanitize_callback' => '\absint',
				'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param >= 0; },
				'default'           => 0,
			],
		];
	}

	// NEW: Helper method for PUT /shifts/{id} body arguments
	private function get_shift_update_args(): array {
		$args = $this->get_shift_args(); // Start with the create args

		// Shift ID comes from URL, define it here for completeness
		$args['shift_id'] = [
			'required'          => true,
			'type'              => 'integer',
			'description'       => \__( 'Unique identifier for the shift (from URL).', 'wp-schedule-plugin' ),
			'validate_callback' => function( $param ) { return \is_numeric( $param ) && $param > 0; },
			'sanitize_callback' => '\absint',
		];

		// Make all body parameters optional for update
		$args['org_id']['required'] = false; // Not required in body (used for permission check based on existing shift)
		$args['start_time']['required'] = false;
		$args['end_time']['required'] = false;
		$args['resource_id']['required'] = false;
		$args['user_id']['required'] = false;
		$args['title']['required'] = false;
		$args['notes']['required'] = false;
		$args['status']['required'] = false;
		unset($args['status']['default']); // Don't force default on update if not provided

		// Adjust end_time validation to only run if start_time is also provided or exists
		$args['end_time']['validate_callback'] = function( $param, $request, $key ) {
			$end_timestamp = \rest_parse_date( $param );
			if ( $end_timestamp === false ) return false; // Invalid date format

			$start_timestamp = null;
			if ( $request->has_param('start_time') ) {
				$start_timestamp = \rest_parse_date( $request['start_time'] );
			} else {
				// If start_time is not being updated, get it from the existing shift
				$shift_id = \absint( $request['shift_id'] );
				if ($shift_id > 0) {
					$shift = $this->db->get_shift( $shift_id );
					if ($shift && !is_wp_error($shift)) {
						// Assuming start_time is stored in 'Y-m-d H:i:s' UTC format
						$start_timestamp = \strtotime( $shift->start_time . ' UTC' );
					}
				}
			}

			if ( $start_timestamp === false || $start_timestamp === null ) return true; // Cannot validate against start time

			return $end_timestamp > $start_timestamp;
		};

		return $args;
	}


	// --- Permission Callbacks ---

	public function permission_check_callback( WP_REST_Request $request ): bool|WP_Error {
		if ( ! \current_user_can( 'access_schemaplugin' ) ) {
			return new WP_Error( 'rest_forbidden', \__( 'Sorry, you do not have permission to access this endpoint.', 'wp-schedule-plugin' ), [ 'status' => \rest_authorization_required_code() ] );
		}
		return true;
	}

	public function permission_check_manage_options( WP_REST_Request $request ): bool|WP_Error {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden_context', \__( 'Sorry, you are not allowed to manage options.', 'wp-schedule-plugin' ), [ 'status' => \rest_authorization_required_code() ] );
		}
		return true;
	}


	// --- Helper Methods ---

	protected function verify_nonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce ) {
			$nonce = $request->get_param( '_wpnonce' );
		}
		return \wp_verify_nonce( $nonce, 'wp_rest' );
	}

	protected function api_response( bool $success, string $message_key, array $data = [], int $status_code = 200, ?array $headers = null ): \WP_REST_Response {
		$response_data = [
			'success' => $success,
			'message' => \__( $message_key, 'wp-schedule-plugin' ),
		];
		if ( ! empty( $data ) ) {
			$response_data['data'] = $data;
		}
		if ( ! $success && $status_code === 200 ) {
			$status_code = 400;
		}
		$response = new \WP_REST_Response( $response_data, $status_code );
		if ( ! empty( $headers ) ) {
			foreach ( $headers as $key => $value ) {
				$response->header( $key, $value );
			}
		}
		return $response;
	}

	protected function get_validated_org_id( WP_REST_Request $request, ?string $min_plugin_role = null, string $param_name = 'org_id' ): int|WP_Error {
		// Determine if we should look in query params or body/path params
		$param_source = $request->get_method() === 'GET' ? $request->get_query_params() : $request->get_params();

		// Check if the parameter exists in the determined source
		if ( ! isset( $param_source[ $param_name ] ) ) {
			// If it's a GET request, it might be in the path, check route params as fallback
			if ($request->get_method() === 'GET' && isset($request[$param_name])) {
				$org_id = $request[$param_name];
			} else {
				// Also check body params explicitly if not found elsewhere (e.g., PUT/POST)
				$body_params = $request->get_body_params();
				if (isset($body_params[$param_name])) {
					$org_id = $body_params[$param_name];
				} else {
					// Check route params as a last resort (e.g., for DELETE where org_id isn't in query/body but needed for permission)
					if (isset($request[$param_name])) {
						$org_id = $request[$param_name];
					} else {
						return new WP_Error( 'rest_missing_param', \sprintf( \__( 'Missing required parameter "%s".', 'wp-schedule-plugin' ), $param_name ), [ 'status' => 400 ] );
					}
				}
			}
		} else {
			$org_id = $param_source[ $param_name ];
		}

		$org_id = \absint( $org_id );

		if ( $org_id <= 0 ) {
			return new WP_Error( 'rest_invalid_param', \sprintf( \__( 'Invalid organization ID provided in parameter "%s".', 'wp-schedule-plugin' ), $param_name ), [ 'status' => 400 ] );
		}
		$organization = $this->db->get_organization( $org_id );
		if ( ! $organization ) {
			return new WP_Error( 'rest_not_found', \__( 'Organization not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
		}
		if ( $min_plugin_role !== null ) {
			$user_id = \get_current_user_id();
			if ( $user_id <= 0 ) {
				return new WP_Error( 'rest_not_logged_in', \__( 'You must be logged in.', 'wp-schedule-plugin' ), [ 'status' => 401 ] );
			}
			if ( \current_user_can( 'manage_options' ) ) {
				return $org_id;
			}
			// Check plugin role in org permissions table
			if ( ! $this->db->user_has_org_role( $user_id, $org_id, $min_plugin_role ) ) {
				return new WP_Error( 'rest_forbidden_role', \sprintf( \__( 'You do not have the required plugin role ("%s") for this action in this organization.', 'wp-schedule-plugin' ), $min_plugin_role ), [ 'status' => 403 ] );
			}
		}
		return $org_id;
	}


	// --- Endpoint Handlers ---

	public function handle_test_request( WP_REST_Request $request ): \WP_REST_Response {
		return $this->api_response( true, 'apiTestSuccess', [ 'timestamp' => \time() ] );
	}

	// --- Organization Handlers (Fas 1) ---

	public function create_organization_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		$name = $request->get_param('name');
		$parent_org_id = $request->get_param('parent_org_id'); // Sanitized by args definition
		if ( $parent_org_id === 0 ) $parent_org_id = null;

		if ( $parent_org_id !== null ) {
			$parent_org = $this->db->get_organization( $parent_org_id );
			if ( ! $parent_org ) {
				return new WP_Error( 'rest_invalid_param', \__( 'Invalid parent organization ID provided.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
			}
		}
		$new_org_id = $this->db->create_organization( $name, $parent_org_id );
		if ( $new_org_id === false ) {
			return $this->api_response( false, 'orgCreateFailedDb', [], 500 );
		}
		$new_organization = $this->db->get_organization( $new_org_id );
		return $this->api_response( true, 'orgCreateSuccess', [ 'organization' => $new_organization ], 201 );
	}

	public function get_organizations_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( current_user_can( 'manage_options' ) ) {
			// Admins get all organizations, with pagination support
			$args = [];
			// Support limit, number, offset, page, per_page
			$limit = $request->get_param('limit');
			$number = $request->get_param('number');
			$offset = $request->get_param('offset');
			$page = $request->get_param('page');
			$per_page = $request->get_param('per_page');
			if ($limit !== null) $args['limit'] = (int)$limit;
			elseif ($number !== null) $args['number'] = (int)$number;
			if ($offset !== null) $args['offset'] = (int)$offset;
			// page/per_page to offset/limit
			if ($per_page !== null) {
				$args['limit'] = (int)$per_page;
				if ($page !== null) {
					$args['offset'] = ((int)$page - 1) * (int)$per_page;
				}
			}
			$organizations = $this->db->get_organizations( $args );
		} else {
			// Non-admins get only organizations they are a member of
			$organizations = $this->db->get_user_memberships( $user_id );
		}
		return $this->api_response( true, 'orgFetchSuccess', [ 'organizations' => $organizations ] );
	}

	public function get_organization_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// org_id is validated by permission callback
		$org_id = \absint( $request['org_id'] );
		$organization = $this->db->get_organization( $org_id );
		// Permission callback already checked existence, but double check is safe
		if ( ! $organization ) {
			return new WP_Error( 'rest_not_found', \__( 'Organization not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
		}
		$user_id = \get_current_user_id();
		if ( $user_id > 0 ) {
			$organization->current_user_plugin_roles = $this->db->get_user_org_roles( $user_id, $org_id );
		} else {
			$organization->current_user_plugin_roles = [];
		}
		return $this->api_response( true, 'orgFetchSuccess', [ 'organization' => $organization ] );
	}

	public function update_organization_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// org_id is validated by permission callback
		$org_id = \absint( $request['org_id'] );
		$params = $request->get_params(); // Sanitized by args definition
		$update_data = [];

		if ( isset( $params['name'] ) ) {
			// Validation callback in args already checked non-empty
			$update_data['name'] = $params['name'];
		}
		if ( \array_key_exists( 'parent_org_id', $params ) ) {
			$parent_org_id = $params['parent_org_id']; // Already sanitized/validated
			if ( $parent_org_id !== null ) {
				if ( $parent_org_id === $org_id ) {
					return new WP_Error( 'rest_invalid_parent', \__( 'An organization cannot be its own parent.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
				}
				// $descendants = $this->db->get_organization_descendants( $org_id ); // Fas 5
				// if ( \in_array( $parent_org_id, $descendants, true ) ) {
				//  return new WP_Error( 'rest_cyclical_parent', \__( 'Cannot set a descendant as a parent organization.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
				// }
				if ( ! \current_user_can( 'manage_options' ) ) {
					return new WP_Error( 'rest_forbidden_hierarchy', \__( 'You do not have permission to change the organization hierarchy.', 'wp-schedule-plugin' ), [ 'status' => 403 ] );
				}
				$update_data['parent_org_id'] = $parent_org_id; // Use the validated value
			} else { // Setting parent to null (root)
				if ( ! \current_user_can( 'manage_options' ) ) {
					$current_org = $this->db->get_organization($org_id);
					if ($current_org && $current_org->parent_org_id !== null) {
						return new WP_Error( 'rest_forbidden_hierarchy', \__( 'You do not have permission to change the organization hierarchy.', 'wp-schedule-plugin' ), [ 'status' => 403 ] );
					}
				}
				$update_data['parent_org_id'] = null;
			}
		}
		if ( empty( $update_data ) ) {
			$organization = $this->db->get_organization( $org_id );
			return $this->api_response( true, 'orgNothingToUpdate', [ 'organization' => $organization ] );
		}
		$success = $this->db->update_organization( $org_id, $update_data );
		if ( ! $success ) {
			return $this->api_response( false, 'orgUpdateFailedDb', [], 500 );
		}
		$updated_organization = $this->db->get_organization( $org_id );
		return $this->api_response( true, 'orgUpdateSuccess', [ 'organization' => $updated_organization ] );
	}

	public function delete_organization_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// org_id is validated by permission callback
		$org_id = \absint( $request['org_id'] );
		$organization = $this->db->get_organization( $org_id );
		if ( ! $organization ) {
			// Should not happen due to permission check, but good practice
			return new WP_Error( 'rest_not_found', \__( 'Organization not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
		}
		// Placeholder for dependency checks and actual delete call
		$success = false;
		\error_log("WP Schedule Plugin: Placeholder - Deleting organization ID {$org_id} is not yet implemented in Database class.");
		// $success = $this->db->delete_organization( $org_id );
		if ( ! $success ) {
			return $this->api_response( false, 'orgDeleteFailedDb', [], 500 );
		}
		return $this->api_response( true, 'orgDeleteSuccess' );
	}


	// --- Member Handlers (Fas 4) ---

	public function get_organization_members_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// org_id is validated by permission callback
		$org_id = \absint( $request['org_id'] );
		$include_user_data = true;
		$args = [];
		// Support number, limit, offset, page, per_page
		$limit = $request->get_param('limit');
		$number = $request->get_param('number');
		$offset = $request->get_param('offset');
		$page = $request->get_param('page');
		$per_page = $request->get_param('per_page');
		if ($limit !== null) $args['number'] = (int)$limit;
		elseif ($number !== null) $args['number'] = (int)$number;
		if ($offset !== null) $args['offset'] = (int)$offset;
		if ($per_page !== null) {
			$args['number'] = (int)$per_page;
			if ($page !== null) {
				$args['offset'] = ((int)$page - 1) * (int)$per_page;
			}
		}
		$members = $this->db->get_organization_members($org_id, $include_user_data, $args);
		return $this->api_response( true, 'membersFetchSuccess', [ 'members' => $members ] );
	}

	public function add_organization_member_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// org_id is validated by permission callback
		$org_id = \absint( $request['org_id'] );
		$user_id = \absint( $request['user_id'] ); // Sanitized by args
		$plugin_role = $request['plugin_role']; // Validated by enum in args

		$user_data = \get_userdata( $user_id );
		if ( ! $user_data ) {
			return new WP_Error( 'rest_user_invalid_id', \__( 'Invalid user ID provided.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
		}
		// Only allow users with WP role "schema user"
		if ( ! in_array( 'schema_user', (array)$user_data->roles, true ) ) {
			return new WP_Error( 'rest_user_not_schema_user', \__( 'User must have the "schema user" role to be assigned plugin roles.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
		}
		$success = $this->db->add_org_permission( $user_id, $org_id, $plugin_role );
		if ( ! $success ) {
			return $this->api_response( false, 'memberAddFailedDb', [], 500 );
		}
		// Return the new member's plugin roles
		$roles = $this->db->get_user_org_roles( $user_id, $org_id );
		return $this->api_response( true, 'memberAddSuccess', [
			'member' => [
				'user_id' => $user_id,
				'plugin_roles' => $roles,
				'display_name' => $user_data->display_name,
				'user_email' => $user_data->user_email,
			]
		], 201 );
	}

	public function update_organization_member_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// org_id is validated by permission callback
		$org_id = \absint( $request['org_id'] );
		$user_id = \absint( $request['user_id'] ); // From URL path
		$params = $request->get_params(); // Sanitized by args

		$user_data = \get_userdata($user_id);
		if (!$user_data) {
			return new WP_Error( 'rest_user_invalid_id', \__( 'Invalid user ID provided.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
		}
		// Only allow users with WP role "schema user"
		if ( ! in_array( 'schema_user', (array)$user_data->roles, true ) ) {
			return new WP_Error( 'rest_user_not_schema_user', \__( 'User must have the "schema user" role to be assigned plugin roles.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
		}

		// Update plugin roles: expects 'plugin_roles' as array in params
		if ( isset($params['plugin_roles']) && is_array($params['plugin_roles']) ) {
			// Remove all current roles for this user/org, then add the new ones
			$current_roles = $this->db->get_user_org_roles($user_id, $org_id);
			foreach ($current_roles as $role) {
				$this->db->remove_org_permission($user_id, $org_id, $role);
			}
			foreach ($params['plugin_roles'] as $role) {
				$this->db->add_org_permission($user_id, $org_id, $role);
			}
		} else {
			return new WP_Error( 'rest_missing_plugin_roles', \__( 'Missing or invalid plugin_roles array.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
		}

		// Return updated member info
		$roles = $this->db->get_user_org_roles($user_id, $org_id);
		return $this->api_response( true, 'memberUpdateSuccess', [
			'member' => [
				'user_id' => $user_id,
				'plugin_roles' => $roles,
				'display_name' => $user_data->display_name,
				'user_email' => $user_data->user_email,
			]
		]);
	}

	public function delete_organization_member_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// org_id is validated by permission callback
		$org_id = \absint( $request['org_id'] );
		$user_id = \absint( $request['user_id'] ); // From URL path

		if ( $user_id === \get_current_user_id() ) {
			return new WP_Error( 'rest_cannot_remove_self', \__( 'You cannot remove yourself from an organization.', 'wp-schedule-plugin' ), [ 'status' => 403 ] );
		}
		// Remove all plugin roles for this user/org
		$current_roles = $this->db->get_user_org_roles($user_id, $org_id);
		if (empty($current_roles)) {
			return new WP_Error( 'rest_member_not_found', \__( 'Membership not found.', 'wp-schedule-plugin' ), [ 'status' => 404 ] );
		}
		$success = true;
		foreach ($current_roles as $role) {
			$success = $success && $this->db->remove_org_permission($user_id, $org_id, $role);
		}
		if ( ! $success ) {
			return $this->api_response( false, 'memberRemoveFailedDb', [], 500 );
		}
		return $this->api_response( true, 'memberRemoveSuccess' );
	}
// --- Resource Handlers (Fas 2) ---

public function create_resource_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
	// org_id is validated by the permission callback
	$org_id = \absint( $request['org_id'] );
	$name = $request->get_param('name'); // Sanitized & validated by args

	// Collect optional parameters using get_param to respect defaults/sanitization
	$data = [];
	$optional_params = [ 'description', 'type', 'capacity', 'is_active' ];
	foreach ( $optional_params as $param ) {
		// Only include if the parameter was actually sent in the request,
		// or if it's 'is_active' which has a default we want to pass.
		if ( $request->has_param( $param ) || $param === 'is_active' ) {
			$data[ $param ] = $request->get_param( $param );
		}
	}

	$new_resource_id = $this->db->create_resource( $org_id, $name, $data );

	if ( $new_resource_id === false || \is_wp_error( $new_resource_id ) ) {
		$error_message = 'resourceCreateFailedDb';
		$error_code = 500;
		$error_data = [];
		if ( \is_wp_error( $new_resource_id ) ) {
			$error_message = $new_resource_id->get_error_message() ?: $error_message;
			$error_code_from_error = $new_resource_id->get_error_code(); // Can be string or int
			$status_from_data = $new_resource_id->get_error_data()['status'] ?? null;
			$error_code = $status_from_data ?? (\is_numeric($error_code_from_error) ? (int)$error_code_from_error : 500);
			$error_data = $new_resource_id->get_error_data();
		}
		\error_log("WP Schedule Plugin: Failed to create resource for org {$org_id}. DB Error: " . ($new_resource_id instanceof WP_Error ? $new_resource_id->get_error_message() : 'Unknown DB error'));
		return $this->api_response( false, $error_message, $error_data, $error_code );
	}

	// Fetch the newly created resource to return it
	$new_resource = $this->db->get_resource( $new_resource_id );

	if ( ! $new_resource || \is_wp_error( $new_resource ) ) {
		\error_log("WP Schedule Plugin: Created resource (ID: {$new_resource_id}) but failed to fetch it afterwards.");
		// Return success but indicate the resource couldn't be retrieved immediately
		return $this->api_response( true, 'resourceCreateSuccessButFetchFailed', [ 'resource_id' => $new_resource_id ], 201 );
	}

	return $this->api_response( true, 'resourceCreateSuccess', [ 'resource' => $new_resource ], 201 );
}

public function get_resources_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
	// org_id is validated by the permission callback
	$org_id = \absint( $request['org_id'] );

	// Build args array for database query from sanitized query params
	$db_args = [
		'org_id' => $org_id, // Always include the validated org_id
	];

	$query_params = [ 'type', 'is_active', 'number', 'offset', 'limit', 'page', 'per_page' ];
	foreach ( $query_params as $param ) {
		if ( $request->has_param( $param ) ) {
			$value = $request->get_param( $param );
			$db_args[ $param ] = $value;
		}
	}
	// page/per_page to offset/number
	if (isset($db_args['per_page'])) {
		$db_args['number'] = (int)$db_args['per_page'];
		if (isset($db_args['page'])) {
			$db_args['offset'] = ((int)$db_args['page'] - 1) * (int)$db_args['per_page'];
		}
	}
	// limit as alias for number
	if (isset($db_args['limit'])) {
		$db_args['number'] = (int)$db_args['limit'];
	}

	// Fetch resources from the database
	$resources = $this->db->get_resources( $db_args );

	if ( \is_wp_error( $resources ) ) {
		\error_log("WP Schedule Plugin: Error fetching resources for org {$org_id}: " . $resources->get_error_message());
		return $resources; // Forward the WP_Error
	}

	$headers = [];

	return $this->api_response( true, 'resourcesFetchSuccess', [ 'resources' => $resources ], 200, $headers );
}

	// NEW: Handler for GET /resources/{resource_id}
	public function get_resource_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// resource_id is validated and resource existence/permissions checked by the permission callback
		$resource_id = \absint( $request['resource_id'] );

		// Fetch the resource again (permission callback already did, but this is cleaner)
		$resource = $this->db->get_resource( $resource_id );

		// Double-check for safety, though permission callback should prevent this state
		if ( ! $resource || \is_wp_error( $resource ) ) {
			// Log error because this shouldn't happen if permission check passed
			\error_log("WP Schedule Plugin: get_resource_handler reached for non-existent/error resource ID {$resource_id} after permission check.");
			return new WP_Error( 'rest_internal_error', \__( 'An unexpected error occurred while fetching the resource.', 'wp-schedule-plugin' ), [ 'status' => 500 ] );
		}

		// Return the single resource
		return $this->api_response( true, 'resourceFetchSuccess', [ 'resource' => $resource ] );
	}
	public function update_resource_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// resource_id is validated and permissions checked by the permission callback
		$resource_id = \absint( $request['resource_id'] );

		// Get all parameters that were actually sent in the request body
		$params = $request->get_params(); // Already sanitized/validated by args

		$update_data = [];
		$allowed_update_fields = [ 'name', 'description', 'type', 'capacity', 'is_active' ];

		foreach ( $allowed_update_fields as $field ) {
			// Use array_key_exists to check if the key was present in the request,
			// even if the value is null (e.g., setting description to null).
			if ( \array_key_exists( $field, $params ) ) {
				// Use get_param to ensure sanitization/validation logic is applied again if needed,
				// though it should have happened already via the 'args' definition.
				$update_data[ $field ] = $request->get_param( $field );
			}
		}

		// If no valid update fields were provided
		if ( empty( $update_data ) ) {
			$resource = $this->db->get_resource( $resource_id );
			// If resource fetch fails here, something is wrong (should exist due to permission check)
			if ( ! $resource || \is_wp_error( $resource ) ) {
				\error_log("WP Schedule Plugin: update_resource_handler - Failed to fetch resource ID {$resource_id} after permission check when no update data was provided.");
				return new WP_Error( 'rest_internal_error', \__( 'An unexpected error occurred while fetching the resource.', 'wp-schedule-plugin' ), [ 'status' => 500 ] );
			}
			return $this->api_response( true, 'resourceNothingToUpdate', [ 'resource' => $resource ] );
		}

		// Attempt to update the resource in the database
		$success = $this->db->update_resource( $resource_id, $update_data );

		if ( ! $success || \is_wp_error( $success ) ) {
			$error_message = 'resourceUpdateFailedDb';
			$error_code = 500;
			$error_data = [];
			if ( \is_wp_error( $success ) ) {
				$error_message = $success->get_error_message() ?: $error_message;
				$error_code_from_error = $success->get_error_code();
				$status_from_data = $success->get_error_data()['status'] ?? null;
				$error_code = $status_from_data ?? (\is_numeric($error_code_from_error) ? (int)$error_code_from_error : 500);
				$error_data = $success->get_error_data();
			}
			\error_log("WP Schedule Plugin: Failed to update resource ID {$resource_id}. DB Error: " . ($success instanceof WP_Error ? $success->get_error_message() : 'Unknown DB error'));
			return $this->api_response( false, $error_message, $error_data, $error_code );
		}

		// Fetch the updated resource to return it
		$updated_resource = $this->db->get_resource( $resource_id );

		if ( ! $updated_resource || \is_wp_error( $updated_resource ) ) {
			\error_log("WP Schedule Plugin: Updated resource (ID: {$resource_id}) but failed to fetch it afterwards.");
			// Return success but indicate the resource couldn't be retrieved immediately
			// Include the data we attempted to update for context.
			return $this->api_response( true, 'resourceUpdateSuccessButFetchFailed', [ 'resource_id' => $resource_id, 'updated_data' => $update_data ], 200 );
		}

		return $this->api_response( true, 'resourceUpdateSuccess', [ 'resource' => $updated_resource ] );
	}

	/**
	 * Handles DELETE request for a specific resource.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function delete_resource_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// resource_id is validated and permissions checked by the permission callback
		$resource_id = \absint( $request['resource_id'] );

		// Optional: Check if the resource is currently in use (e.g., assigned to shifts)
		// if ( $this->db->is_resource_in_use( $resource_id ) ) { // Assuming this method exists
		//  return new WP_Error(
		//      'rest_resource_in_use',
		//      \__( 'Cannot delete resource because it is currently assigned to one or more shifts.', 'wp-schedule-plugin' ),
		//      [ 'status' => 409 ] // 409 Conflict
		//  );
		// }

		// Attempt to delete the resource from the database
		$success = $this->db->delete_resource( $resource_id );

		if ( ! $success ) {
			// The permission callback should have caught non-existent resources,
			// so this likely indicates a database error.
			\error_log("WP Schedule Plugin: Failed to delete resource ID {$resource_id}. Database error suspected.");
			return $this->api_response( false, 'resourceDeleteFailedDb', [], 500 );
		}

		// Return success response
		return $this->api_response( true, 'resourceDeleteSuccess' );
	}


	// --- Handlers for Fas 3 (Shifts) will go here ---

	/**
	 * Handles POST request to create a new shift.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function create_shift_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// org_id is validated by the permission callback
		$org_id = \absint( $request['org_id'] );

		// Get required parameters (already validated and sanitized by args)
		$start_time = $request['start_time'];
		$end_time = $request['end_time'];

		// Collect optional parameters using get_param to respect sanitization
		$data = [];
		$optional_params = [ 'resource_id', 'user_id', 'title', 'notes', 'status' ];
		foreach ( $optional_params as $param ) {
			// Only include if the parameter was actually sent in the request
			// or if it has a default we want to pass (like 'status').
			if ( $request->has_param( $param ) || $param === 'status' ) {
				$value = $request->get_param( $param );
				// Ensure nulls are passed correctly if intended
				if ( $value !== null ) {
					$data[ $param ] = $value;
				} elseif ( \array_key_exists( $param, $request->get_body_params() ) ) {
					// If the key exists in the body but the sanitized value is null, pass null
					$data[ $param ] = null;
				}
			}
		}

		// Validate that user_id (if provided) is a member of the org
		if ( isset( $data['user_id'] ) && $data['user_id'] !== null ) {
			$member = $this->db->get_member( $data['user_id'], $org_id );
			if ( ! $member ) {
				return new WP_Error( 'rest_invalid_member', \__( 'Assigned user is not a member of this organization.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
			}
		}

		// Validate that resource_id (if provided) belongs to the org
		if ( isset( $data['resource_id'] ) && $data['resource_id'] !== null ) {
			$resource = $this->db->get_resource( $data['resource_id'] );
			if ( ! $resource || $resource->org_id !== $org_id ) {
				return new WP_Error( 'rest_invalid_resource', \__( 'Assigned resource does not belong to this organization.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
			}
		}

		// Attempt to create the shift in the database
		$new_shift_id = $this->db->create_shift( $org_id, $start_time, $end_time, $data );

		if ( $new_shift_id === false || \is_wp_error( $new_shift_id ) ) {
			$error_message = 'shiftCreateFailedDb';
			$error_code = 500;
			$error_data = [];
			if ( \is_wp_error( $new_shift_id ) ) {
				$error_message = $new_shift_id->get_error_message() ?: $error_message;
				$error_code_from_error = $new_shift_id->get_error_code();
				$status_from_data = $new_shift_id->get_error_data()['status'] ?? null;
				$error_code = $status_from_data ?? (\is_numeric($error_code_from_error) ? (int)$error_code_from_error : 500);
				$error_data = $new_shift_id->get_error_data();
			}
			\error_log("WP Schedule Plugin: Failed to create shift for org {$org_id}. DB Error: " . ($new_shift_id instanceof WP_Error ? $new_shift_id->get_error_message() : 'Unknown DB error'));
			return $this->api_response( false, $error_message, $error_data, $error_code );
		}

		// Fetch the newly created shift to return it
		$new_shift = $this->db->get_shift( $new_shift_id );

		if ( ! $new_shift || \is_wp_error( $new_shift ) ) {
			\error_log("WP Schedule Plugin: Created shift (ID: {$new_shift_id}) but failed to fetch it afterwards.");
			// Return success but indicate the shift couldn't be retrieved immediately
			return $this->api_response( true, 'shiftCreateSuccessButFetchFailed', [ 'shift_id' => $new_shift_id ], 201 );
		}

		return $this->api_response( true, 'shiftCreateSuccess', [ 'shift' => $new_shift ], 201 );
	}

	/**
		* Handles GET request to retrieve shifts.
		*
		* @param WP_REST_Request $request The request object.
		* @return \WP_REST_Response|WP_Error Response object or WP_Error on failure.
		*/
	public function get_shifts_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// org_id is validated by the permission callback, which checks 'employee' role
		$org_id = $this->get_validated_org_id( $request, 'employee', 'org_id' );
		if ( \is_wp_error( $org_id ) ) {
			return $org_id; // Return error if validation failed
		}

		// Build args array for database query from sanitized query params
		$db_args = [
			'org_id' => $org_id, // Always include the validated org_id
		];

		$query_params = [ 'resource_id', 'user_id', 'status', 'start_date', 'end_date', 'number', 'offset', 'limit', 'page', 'per_page' ];
		foreach ( $query_params as $param ) {
			if ( $request->has_param( $param ) ) {
				$value = $request->get_param( $param );
				if ( $value !== null && $value !== '' ) {
					 $db_args[ $param ] = $value;
				}
			}
		}
		// page/per_page to offset/limit
		if (isset($db_args['per_page'])) {
			$db_args['limit'] = (int)$db_args['per_page'];
			if (isset($db_args['page'])) {
				$db_args['offset'] = ((int)$db_args['page'] - 1) * (int)$db_args['per_page'];
			}
		}
		// limit as alias for number
		if (isset($db_args['limit'])) {
			$db_args['number'] = (int)$db_args['limit'];
		}

		// Fetch shifts from the database
		$shifts = $this->db->get_shifts( $db_args );

		if ( \is_wp_error( $shifts ) ) {
			\error_log("WP Schedule Plugin: Error fetching shifts for org {$org_id}: " . $shifts->get_error_message());
			return $shifts; // Forward the WP_Error
		}

		$headers = [];

		return $this->api_response( true, 'shiftsFetchSuccess', [ 'shifts' => $shifts ], 200, $headers );
	}

	/**
	 * Handles GET request for a specific shift.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function get_shift_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// shift_id is validated and shift existence/permissions checked by the permission callback
		$shift_id = \absint( $request['shift_id'] );

		// Fetch the shift again (permission callback already did, but this is cleaner)
		$shift = $this->db->get_shift( $shift_id );

		// Double-check for safety, though permission callback should prevent this state
		if ( ! $shift || \is_wp_error( $shift ) ) {
			// Log error because this shouldn't happen if permission check passed
			\error_log("WP Schedule Plugin: get_shift_handler reached for non-existent/error shift ID {$shift_id} after permission check.");
			return new WP_Error( 'rest_internal_error', \__( 'An unexpected error occurred while fetching the shift.', 'wp-schedule-plugin' ), [ 'status' => 500 ] );
		}

		// Return the single shift
		return $this->api_response( true, 'shiftFetchSuccess', [ 'shift' => $shift ] );
	}

	/**
		* Handles PUT/PATCH request to update an existing shift.
		*
		* @param WP_REST_Request $request The request object.
		* @return \WP_REST_Response|WP_Error Response object or WP_Error on failure.
		*/
	public function update_shift_handler( WP_REST_Request $request ): \WP_REST_Response|WP_Error {
		// shift_id is validated and permissions checked by the permission callback
		$shift_id = \absint( $request['shift_id'] );

		// Get the existing shift's org_id for validation purposes
		$existing_shift = $this->db->get_shift( $shift_id );
		if ( ! $existing_shift || \is_wp_error( $existing_shift ) ) {
			// Should not happen if permission check passed, but good practice
			\error_log("WP Schedule Plugin: update_shift_handler - Could not retrieve shift {$shift_id} after permission check.");
			return new WP_Error( 'rest_internal_error', \__( 'An unexpected error occurred while retrieving shift data.', 'wp-schedule-plugin' ), [ 'status' => 500 ] );
		}
		$org_id = $existing_shift->org_id;

		// Get all parameters that were actually sent in the request body
		$params = $request->get_params(); // Already sanitized/validated by args

		$update_data = [];
		$allowed_update_fields = [ 'start_time', 'end_time', 'resource_id', 'user_id', 'title', 'notes', 'status' ];

		foreach ( $allowed_update_fields as $field ) {
			// Use array_key_exists to check if the key was present in the request,
			// even if the value is null (e.g., setting notes to null).
			if ( \array_key_exists( $field, $params ) ) {
				// Use get_param to ensure sanitization/validation logic is applied again if needed
				$update_data[ $field ] = $request->get_param( $field );
			}
		}

		// If no valid update fields were provided
		if ( empty( $update_data ) ) {
			return $this->api_response( true, 'shiftNothingToUpdate', [ 'shift' => $existing_shift ] );
		}

		// --- Additional Validations based on updated data ---

		// Validate that user_id (if being updated) is a member of the org
		if ( isset( $update_data['user_id'] ) && $update_data['user_id'] !== null ) {
			$member = $this->db->get_member( $update_data['user_id'], $org_id );
			if ( ! $member ) {
				return new WP_Error( 'rest_invalid_member', \__( 'Assigned user is not a member of this organization.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
			}
		}

		// Validate that resource_id (if being updated) belongs to the org
		if ( isset( $update_data['resource_id'] ) && $update_data['resource_id'] !== null ) {
			$resource = $this->db->get_resource( $update_data['resource_id'] );
			if ( ! $resource || $resource->org_id !== $org_id ) {
				return new WP_Error( 'rest_invalid_resource', \__( 'Assigned resource does not belong to this organization.', 'wp-schedule-plugin' ), [ 'status' => 400 ] );
			}
		}

		// Validate start_time/end_time consistency (handled by 'args' validation callback)

		// --- Attempt to update the shift in the database ---
		$success = $this->db->update_shift( $shift_id, $update_data );

		if ( ! $success || \is_wp_error( $success ) ) {
			$error_message = 'shiftUpdateFailedDb';
			$error_code = 500;
			$error_data = [];
			if ( \is_wp_error( $success ) ) {
				$error_message = $success->get_error_message() ?: $error_message;
				$error_code_from_error = $success->get_error_code();
				$status_from_data = $success->get_error_data()['status'] ?? null;
				$error_code = $status_from_data ?? (\is_numeric($error_code_from_error) ? (int)$error_code_from_error : 500);
				$error_data = $success->get_error_data();
			}
			\error_log("WP Schedule Plugin: Failed to update shift ID {$shift_id}. DB Error: " . ($success instanceof WP_Error ? $success->get_error_message() : 'Unknown DB error'));
			return $this->api_response( false, $error_message, $error_data, $error_code );
		}

		// Fetch the updated shift to return it
		$updated_shift = $this->db->get_shift( $shift_id );

		if ( ! $updated_shift || \is_wp_error( $updated_shift ) ) {
			\error_log("WP Schedule Plugin: Updated shift (ID: {$shift_id}) but failed to fetch it afterwards.");
			// Return success but indicate the shift couldn't be retrieved immediately
			return $this->api_response( true, 'shiftUpdateSuccessButFetchFailed', [ 'shift_id' => $shift_id, 'updated_data' => $update_data ], 200 );
		}

		return $this->api_response( true, 'shiftUpdateSuccess', [ 'shift' => $updated_shift ] );
	}
	/**
	 * Handles DELETE request for a specific shift.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|WP_Error Response object or WP_Error on failure.
	 */
	public function delete_shift_handler( WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		// shift_id is validated and permissions checked by the permission callback
		$shift_id = \absint( $request['shift_id'] );

		// Attempt to delete the shift from the database
		$success = $this->db->delete_shift( $shift_id );

		if ( ! $success ) {
			// The permission callback should have caught non-existent shifts,
			// so this likely indicates a database error or constraint violation.
			\error_log("WP Schedule Plugin: Failed to delete shift ID {$shift_id}. Database error suspected.");
			return $this->api_response( false, 'shiftDeleteFailedDb', [], 500 );
		}

		// Return success response
		return $this->api_response( true, 'shiftDeleteSuccess' );
	}

	/**
	 * Handle GET /dashboard-stats
	 * Returns summary statistics for the dashboard (KPI).
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function handle_dashboard_stats( WP_REST_Request $request ) {
		// TODO: Implement actual statistics logic
		$data = [
			'upcoming_shifts' => 0,
			'resources' => 0,
			'coverage' => 0,
			'open_shifts' => 0,
		];
		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Handle GET /shifts/summary
	 * Returns aggregated shift data, grouped by week.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function handle_shifts_summary( WP_REST_Request $request ) {
		$group_by = $request->get_param( 'group_by' ) ?: 'week';
		// TODO: Implement actual aggregation logic
		$data = [
			[
				'week' => '2025-W15',
				'shifts' => 0,
			],
			// ...
		];
		return new \WP_REST_Response( $data, 200 );
	}
} // End of class ApiHandlers
