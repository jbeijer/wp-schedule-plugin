<?php
/**
 * Database handler class for WP Schedule Plugin.
 */

namespace JohanBeijer\WPSchedule;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database operations for the plugin.
 */
class Database {

	/**
	 * WordPress Database instance.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Get the full table name with prefix.
	 *
	 * @param string $name The short table name (e.g., 'organizations').
	 * @return string The full table name.
	 */
	public function get_table_name( string $name ): string {
		return $this->wpdb->prefix . 'wp_schedule_' . $name; // Added 'wp_schedule_' prefix for clarity
	}

	/**
	 * Create or update database tables on activation.
	 * (Implementation in Fas 1 & later)
	 */
	public function create_or_update_tables(): void {
		require_once \ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $this->wpdb->get_charset_collate();
		$sqls = [];

		// Organizations Table
		$table_organizations = $this->get_table_name('organizations');
		$sqls[] = "CREATE TABLE {$table_organizations} (
			org_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			parent_org_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (org_id),
			KEY parent_org_id (parent_org_id)
		) $charset_collate;";

		// Organization Members Table
		$table_members = $this->get_table_name('organization_members');
		$sqls[] = "CREATE TABLE {$table_members} (
			membership_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			org_id bigint(20) unsigned NOT NULL,
			internal_role enum('employee','scheduler','org_admin') NOT NULL DEFAULT 'employee',
			employment_number varchar(100) DEFAULT NULL,
			added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (membership_id),
			UNIQUE KEY user_org (user_id, org_id),
			KEY user_id (user_id),
			KEY org_id (org_id)
		) $charset_collate;";

		// Resources Table (Fas 2)
		$table_resources = $this->get_table_name('resources');
		$table_organizations_for_fk = $this->get_table_name('organizations'); // Need this for FK definition if included directly
		$sqls[] = "CREATE TABLE {$table_resources} (
			resource_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			org_id bigint(20) unsigned NOT NULL,
			name varchar(255) NOT NULL,
			description text DEFAULT NULL,
			type varchar(100) DEFAULT NULL,
			capacity int DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (resource_id),
			KEY org_id (org_id),
			KEY type (type),
			KEY is_active (is_active)
		) $charset_collate;";

		// Shifts Table (Fas 3)
		$table_shifts = $this->get_table_name('shifts');
		// Note: Foreign keys (org_id, resource_id, user_id) should ideally be added
		// using separate ALTER TABLE statements after dbDelta, as dbDelta's
		// support for FOREIGN KEY constraints in CREATE TABLE is limited.
		// We define the columns and indexes here as requested.
		$sqls[] = "CREATE TABLE {$table_shifts} (
			shift_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			org_id bigint(20) unsigned NOT NULL,
			resource_id bigint(20) unsigned DEFAULT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			start_time datetime NOT NULL,
			end_time datetime NOT NULL,
			title varchar(255) DEFAULT NULL,
			notes text DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (shift_id),
			KEY org_id (org_id),
			KEY resource_id (resource_id),
			KEY user_id (user_id),
			KEY start_time (start_time),
			KEY end_time (end_time),
			KEY status (status)
		) $charset_collate;";

		// Execute SQL using dbDelta
		foreach ($sqls as $sql) {
			\dbDelta( $sql );
		}

		// Check for errors (optional but recommended)
		// global $EZSQL_ERROR;
		// if (!empty($EZSQL_ERROR)) {
		//     error_log('dbDelta errors: ' . print_r($EZSQL_ERROR, true));
		// }
	}

	// --- Organization Methods (Fas 1) ---

	/**
	 * Creates a new organization.
	 *
	 * @param string $name          The name of the organization.
	 * @param int|null $parent_org_id The ID of the parent organization, or null for root.
	 * @return int|false The new organization ID on success, false on failure.
	 */
	public function create_organization( string $name, ?int $parent_org_id = null ) {
		$table_name = $this->get_table_name('organizations');

		// Sanitize name
		$name = \sanitize_text_field( $name );
		if ( empty( $name ) ) {
			return false; // Name is required
		}

		// Prepare data
		$data = [ 'name' => $name ];
		$format = [ '%s' ]; // Format for name

		// Handle parent ID (ensure it's null or a positive integer)
		if ( $parent_org_id !== null ) {
			$parent_org_id = \absint( $parent_org_id );
			if ( $parent_org_id > 0 ) {
				// Optional: Check if parent_org_id actually exists? Could be done here or in API handler.
				$data['parent_org_id'] = $parent_org_id;
				$format[] = '%d'; // Format for parent_org_id
			} else {
				// Treat invalid parent ID (0 or negative) as null (root)
				$data['parent_org_id'] = null;
				$format[] = null; // Use null format placeholder if needed, or adjust format array
			}
		} else {
			$data['parent_org_id'] = null;
			$format[] = null;
		}

		// Insert into database
		$result = $this->wpdb->insert( $table_name, $data, $format );

		if ( $result === false ) {
			error_log( "WP Schedule Plugin: Failed to insert organization '{$name}'. DB Error: " . $this->wpdb->last_error );
			return false;
		}

		return $this->wpdb->insert_id; // Return the new org_id
	}

	/**
	 * Retrieves a single organization by its ID.
	 *
	 * @param int $org_id The organization ID.
	 * @return object|null The organization object (stdClass) or null if not found.
	 */
	public function get_organization( int $org_id ): ?object {
		$table_name = $this->get_table_name('organizations');
		$org_id = \absint( $org_id ); // Ensure positive integer

		if ( $org_id <= 0 ) {
			return null;
		}

		// Prepare the SQL query
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE org_id = %d",
			$org_id
		);

		// Execute the query
		$organization = $this->wpdb->get_row( $sql );

		return $organization; // Returns null if no row found
	}

	/**
	 * Retrieves multiple organizations.
	 *
	 * @param array $args Optional arguments for filtering (e.g., parent_id, search). Not implemented yet.
	 * @return array An array of organization objects (stdClass).
	 */
	public function get_organizations( array $args = [] ): array {
		$table_name = $this->get_table_name('organizations');

		// Basic query for now, add filtering later based on $args
		$sql = "SELECT * FROM {$table_name} ORDER BY name ASC";

		$organizations = $this->wpdb->get_results( $sql );

		return is_array( $organizations ) ? $organizations : [];
	}

	/**
	 * Updates an existing organization.
	 *
	 * @param int   $org_id The ID of the organization to update.
	 * @param array $data   Associative array of data to update (e.g., ['name' => 'New Name', 'parent_org_id' => 5]).
	 *                      Use null for parent_org_id to set as root.
	 * @return bool True on success, false on failure.
	 */
	public function update_organization( int $org_id, array $data ): bool {
		$table_name = $this->get_table_name('organizations');
		$org_id = \absint( $org_id );

		if ( $org_id <= 0 ) {
			return false;
		}

		$update_data = [];
		$update_format = [];

		// Sanitize and prepare name if provided
		if ( isset( $data['name'] ) ) {
			$name = \sanitize_text_field( $data['name'] );
			if ( ! empty( $name ) ) {
				$update_data['name'] = $name;
				$update_format[] = '%s';
			} else {
				// Prevent setting an empty name
				return false;
			}
		}

		// Sanitize and prepare parent_org_id if provided
		// Note: Cycle detection should happen in the API handler before calling this.
		if ( array_key_exists( 'parent_org_id', $data ) ) { // Use array_key_exists to allow setting parent to null
			if ( $data['parent_org_id'] === null ) {
				$update_data['parent_org_id'] = null;
				$update_format[] = null; // Let wpdb handle null format
			} else {
				$parent_org_id = \absint( $data['parent_org_id'] );
				if ( $parent_org_id > 0 && $parent_org_id !== $org_id ) { // Prevent self-parenting
					$update_data['parent_org_id'] = $parent_org_id;
					$update_format[] = '%d';
				} elseif ( $parent_org_id === 0 ) { // Allow setting to root via 0
					$update_data['parent_org_id'] = null;
					$update_format[] = null;
				}
				// Ignore invalid parent IDs (negative or self) otherwise
			}
		}

		// Add updated_at timestamp automatically? $wpdb->update doesn't do this like INSERT.
		// We rely on the DB column definition: ON UPDATE CURRENT_TIMESTAMP

		if ( empty( $update_data ) ) {
			return false; // Nothing valid to update
		}

		// Perform the update
		$result = $this->wpdb->update(
			$table_name,
			$update_data,
			[ 'org_id' => $org_id ], // WHERE clause
			$update_format,          // Format for $update_data
			[ '%d' ]                 // Format for WHERE clause
		);

		if ( $result === false ) {
			error_log( "WP Schedule Plugin: Failed to update organization ID {$org_id}. DB Error: " . $this->wpdb->last_error );
			return false;
		}

		return true; // Returns true if updated (or if data was the same), false on error.
	}

	// --- Member Methods (Fas 1) ---

	/**
	 * Adds a user as a member to an organization.
	 *
	 * @param int    $user_id           The WordPress User ID.
	 * @param int    $org_id            The Organization ID.
	 * @param string $internal_role     The role within the organization ('employee', 'scheduler', 'org_admin').
	 * @param string|null $employment_number Optional employment number.
	 * @return int|false The new membership ID on success, false on failure (e.g., duplicate entry, invalid role).
	 */
	public function add_member( int $user_id, int $org_id, string $internal_role, ?string $employment_number = null ) {
		$table_name = $this->get_table_name('organization_members');
		$user_id = \absint( $user_id );
		$org_id = \absint( $org_id );

		// Validate IDs
		if ( $user_id <= 0 || $org_id <= 0 ) {
			return false;
		}

		// Validate role
		$allowed_roles = [ 'employee', 'scheduler', 'org_admin' ];
		if ( ! in_array( $internal_role, $allowed_roles, true ) ) {
			error_log( "WP Schedule Plugin: Invalid internal_role '{$internal_role}' provided for user {$user_id} in org {$org_id}." );
			return false;
		}

		// Prepare data
		$data = [
			'user_id'       => $user_id,
			'org_id'        => $org_id,
			'internal_role' => $internal_role,
		];
		$format = [ '%d', '%d', '%s' ];

		// Prepare employment number if provided
		if ( $employment_number !== null ) {
			$data['employment_number'] = \sanitize_text_field( $employment_number );
			$format[] = '%s';
		} else {
			$data['employment_number'] = null;
			$format[] = null;
		}

		// Insert into database (wpdb->insert handles potential duplicates based on UNIQUE KEY)
		$result = $this->wpdb->insert( $table_name, $data, $format );

		if ( $result === false ) {
			// Check if it was a duplicate entry error
			if ( $this->wpdb->last_error && str_contains( $this->wpdb->last_error, 'Duplicate entry' ) ) {
				error_log( "WP Schedule Plugin: Attempted to add duplicate membership for user {$user_id} in org {$org_id}." );
			} else {
				error_log( "WP Schedule Plugin: Failed to add membership for user {$user_id} in org {$org_id}. DB Error: " . $this->wpdb->last_error );
			}
			return false;
		}

		return $this->wpdb->insert_id; // Return the new membership_id
	}

	/**
	 * Retrieves membership details for a specific user in a specific organization.
	 *
	 * @param int $user_id The WordPress User ID.
	 * @param int $org_id  The Organization ID.
	 * @return object|null The membership object (stdClass) or null if not found.
	 */
	public function get_member( int $user_id, int $org_id ): ?object {
		$table_name = $this->get_table_name('organization_members');
		$user_id = \absint( $user_id );
		$org_id = \absint( $org_id );

		if ( $user_id <= 0 || $org_id <= 0 ) {
			return null;
		}

		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE user_id = %d AND org_id = %d",
			$user_id,
			$org_id
		);

		$member = $this->wpdb->get_row( $sql );

		return $member;
	}

	/**
	 * Retrieves members of a specific organization.
	 *
	 * @param int  $org_id            The Organization ID.
	 * @param bool $include_user_data Whether to join with wp_users table.
	 * @param array $args             Optional args for pagination/search (e.g., 'number', 'offset', 'search').
	 * @return array An array of member objects (stdClass).
	 */
	public function get_organization_members( int $org_id, bool $include_user_data = false, array $args = [] ): array {
		$members_table = $this->get_table_name('organization_members');
		$users_table = $this->wpdb->users;
		$org_id = \absint( $org_id );

		if ( $org_id <= 0 ) {
			return [];
		}

		// Base query parts
		$select = $include_user_data ? "m.*, u.display_name, u.user_email" : "m.*";
		$from = $members_table . " m";
		$join = $include_user_data ? " INNER JOIN {$users_table} u ON m.user_id = u.ID" : "";
		$where = [ $this->wpdb->prepare( "m.org_id = %d", $org_id ) ];
		$orderby = "ORDER BY m.added_at DESC"; // Default order
		$limit = "";

		// Handle search (simple search on display_name or email if joining)
		if ( $include_user_data && ! empty( $args['search'] ) ) {
			$search_term = '%' . $this->wpdb->esc_like( trim( $args['search'] ) ) . '%';
			$where[] = $this->wpdb->prepare( "(u.display_name LIKE %s OR u.user_email LIKE %s)", $search_term, $search_term );
		}

		// Handle pagination
		$number = isset( $args['number'] ) ? \absint( $args['number'] ) : 20; // Default 20 per page
		$offset = isset( $args['offset'] ) ? \absint( $args['offset'] ) : 0;
		if ( $number > 0 ) {
			$limit = $this->wpdb->prepare( "LIMIT %d OFFSET %d", $number, $offset );
		}

		// Construct the final query
		$sql = "SELECT {$select} FROM {$from}{$join} WHERE " . implode( ' AND ', $where ) . " {$orderby} {$limit}";

		$members = $this->wpdb->get_results( $sql );

		return is_array( $members ) ? $members : [];
	}

	/**
	 * Updates an existing membership record.
	 *
	 * @param int   $user_id The WordPress User ID.
	 * @param int   $org_id  The Organization ID.
	 * @param array $data    Associative array of data to update (e.g., ['internal_role' => 'scheduler', 'employment_number' => 'E124']).
	 * @return bool True on success, false on failure.
	 */
	public function update_member( int $user_id, int $org_id, array $data ): bool {
		$table_name = $this->get_table_name('organization_members');
		$user_id = \absint( $user_id );
		$org_id = \absint( $org_id );

		if ( $user_id <= 0 || $org_id <= 0 ) {
			return false;
		}

		$update_data = [];
		$update_format = [];
		$allowed_roles = [ 'employee', 'scheduler', 'org_admin' ];

		// Sanitize and prepare internal_role if provided
		if ( isset( $data['internal_role'] ) ) {
			if ( in_array( $data['internal_role'], $allowed_roles, true ) ) {
				$update_data['internal_role'] = $data['internal_role'];
				$update_format[] = '%s';
			} else {
				error_log( "WP Schedule Plugin: Invalid internal_role '{$data['internal_role']}' provided for update on user {$user_id} in org {$org_id}." );
				// Optionally return false here if role is mandatory for update, or just skip updating it.
			}
		}

		// Sanitize and prepare employment_number if provided (allow setting to null or empty)
		if ( array_key_exists( 'employment_number', $data ) ) {
			if ( $data['employment_number'] === null || $data['employment_number'] === '' ) {
				$update_data['employment_number'] = null;
				$update_format[] = null;
			} else {
				$update_data['employment_number'] = \sanitize_text_field( $data['employment_number'] );
				$update_format[] = '%s';
			}
		}

		if ( empty( $update_data ) ) {
			return false; // Nothing valid to update
		}

		// Perform the update
		$result = $this->wpdb->update(
			$table_name,
			$update_data,
			[ 'user_id' => $user_id, 'org_id' => $org_id ], // WHERE clause
			$update_format,                                  // Format for $update_data
			[ '%d', '%d' ]                                   // Format for WHERE clause
		);

		if ( $result === false ) {
			error_log( "WP Schedule Plugin: Failed to update membership for user {$user_id} in org {$org_id}. DB Error: " . $this->wpdb->last_error );
			return false;
		}

		// $result contains number of rows updated, could be 0 if data was the same.
		// We consider 0 updates as success in this context.
		return true;
	}

	/**
	 * Removes a user's membership from an organization.
	 *
	 * @param int $user_id The WordPress User ID.
	 * @param int $org_id  The Organization ID.
	 * @return bool True on success, false on failure.
	 */
	public function remove_member( int $user_id, int $org_id ): bool {
		$table_name = $this->get_table_name('organization_members');
		$user_id = \absint( $user_id );
		$org_id = \absint( $org_id );

		if ( $user_id <= 0 || $org_id <= 0 ) {
			return false;
		}

		$result = $this->wpdb->delete(
			$table_name,
			[ 'user_id' => $user_id, 'org_id' => $org_id ], // WHERE clause
			[ '%d', '%d' ]                                   // Format for WHERE clause
		);

		if ( $result === false ) {
			error_log( "WP Schedule Plugin: Failed to remove membership for user {$user_id} in org {$org_id}. DB Error: " . $this->wpdb->last_error );
			return false;
		}

		// $result contains number of rows deleted (0 or 1).
		// We consider 0 deletions (if member didn't exist) as success.
		return true;
	}

	/**
	 * Gets the internal role for a specific user in a specific organization.
	 * Optimized for quick permission checks.
	 *
	 * @param int $user_id The WordPress User ID.
	 * @param int $org_id  The Organization ID.
	 * @return string|false The internal role string ('employee', 'scheduler', 'org_admin') or false if not a member.
	 */
	public function get_user_internal_role( int $user_id, int $org_id ): string|false {
		$table_name = $this->get_table_name('organization_members');
		$user_id = \absint( $user_id );
		$org_id = \absint( $org_id );

		if ( $user_id <= 0 || $org_id <= 0 ) {
			return false;
		}

		$sql = $this->wpdb->prepare(
			"SELECT internal_role FROM {$table_name} WHERE user_id = %d AND org_id = %d",
			$user_id,
			$org_id
		);

		$role = $this->wpdb->get_var( $sql );

		return $role ?? false; // Return the role string or false if null (not found)
	}

	/**
	 * Retrieves all organization memberships for a specific user.
	 *
	 * @param int $user_id The WordPress User ID.
	 * @return array An array of membership objects, joined with organization name.
	 */
	public function get_user_memberships( int $user_id ): array {
		$members_table = $this->get_table_name('organization_members');
		$orgs_table = $this->get_table_name('organizations');
		$user_id = \absint( $user_id );

		if ( $user_id <= 0 ) {
			return [];
		}

		$sql = $this->wpdb->prepare(
			"SELECT m.*, o.name as organization_name
			 FROM {$members_table} m
			 INNER JOIN {$orgs_table} o ON m.org_id = o.org_id
			 WHERE m.user_id = %d
			 ORDER BY o.name ASC",
			$user_id
		);

		$memberships = $this->wpdb->get_results( $sql );

		return is_array( $memberships ) ? $memberships : [];
	}

	// --- Placeholder methods for Fas 5 ---
	// public function get_organization_descendants(...) {}


	// --- Resource Methods (Fas 2) ---

	/**
	 * Creates a new resource associated with an organization.
	 *
	 * @param int    $org_id The ID of the organization the resource belongs to.
	 * @param string $name   The name of the resource.
	 * @param array  $data   Optional additional data for the resource:
	 *                       - 'description' (string)
	 *                       - 'type' (string)
	 *                       - 'capacity' (int)
	 *                       - 'is_active' (bool)
	 * @return int|false The new resource ID on success, false on failure.
	 */
	public function create_resource( int $org_id, string $name, array $data = [] ): int|false {
		$table_name = $this->get_table_name('resources');
		$org_id = \absint( $org_id );

		// Validate required fields
		$name = \sanitize_text_field( trim( $name ) );
		if ( $org_id <= 0 || empty( $name ) ) {
			error_log( "WP Schedule Plugin: Invalid org_id ({$org_id}) or empty name provided for create_resource." );
			return false;
		}

		// Prepare base data and format
		$insert_data = [
			'org_id' => $org_id,
			'name'   => $name,
		];
		$format = [ '%d', '%s' ];

		// Process optional data
		if ( isset( $data['description'] ) ) {
			$insert_data['description'] = \sanitize_textarea_field( $data['description'] );
			$format[] = '%s';
		}
		if ( isset( $data['type'] ) ) {
			$insert_data['type'] = \sanitize_key( $data['type'] ); // Use sanitize_key for simple types/slugs
			$format[] = '%s';
		}
		if ( isset( $data['capacity'] ) ) {
			$capacity = \absint( $data['capacity'] );
			if ($capacity > 0) { // Only store positive capacity
				$insert_data['capacity'] = $capacity;
				$format[] = '%d';
			}
		}
		if ( isset( $data['is_active'] ) ) {
			$insert_data['is_active'] = \boolval( $data['is_active'] ) ? 1 : 0; // Store as 1 or 0
			$format[] = '%d';
		}
		// Note: created_at and updated_at are handled by DB defaults

		// Insert into database
		$result = $this->wpdb->insert( $table_name, $insert_data, $format );

		if ( $result === false ) {
			error_log( "WP Schedule Plugin: Failed to insert resource '{$name}' for org {$org_id}. DB Error: " . $this->wpdb->last_error );
			return false;
		}

		return $this->wpdb->insert_id; // Return the new resource_id
	}

	/**
	 * Retrieves a single resource by its ID.
	 *
	 * @param int $resource_id The resource ID.
	 * @return object|null The resource object (stdClass) or null if not found or invalid ID.
	 */
	public function get_resource( int $resource_id ): ?object {
		$resource_id = \absint( $resource_id ); // Ensure positive integer

		if ( $resource_id <= 0 ) {
			return null; // Invalid ID
		}

		$table_name = $this->get_table_name('resources');

		// Prepare the SQL query
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE resource_id = %d",
			$resource_id
		);

		// Execute the query
		$resource = $this->wpdb->get_row( $sql );

		return $resource; // Returns the object or null if no row found
	}
	/**
	 * Retrieves multiple resources based on specified arguments.
	 *
	 * @param array $args Optional arguments for filtering:
	 *                    - 'org_id' (int): Filter by organization ID.
	 *                    - 'type' (string): Filter by resource type.
	 *                    - 'is_active' (bool): Filter by active status.
	 *                    - 'number' (int): Number of resources to retrieve (for pagination).
	 *                    - 'offset' (int): Offset for pagination.
	 * @return array An array of resource objects (stdClass).
	 */
	public function get_resources( array $args = [] ): array {
		$table_name = $this->get_table_name('resources');

		$sql_select = "SELECT *";
		$sql_from = "FROM {$table_name}";
		$sql_where = [];
		$sql_params = [];
		$sql_orderby = "ORDER BY name ASC";
		$sql_limit = "";

		// Build WHERE conditions
		if ( isset( $args['org_id'] ) ) {
			$org_id = \absint( $args['org_id'] );
			if ( $org_id > 0 ) {
				$sql_where[] = "org_id = %d";
				$sql_params[] = $org_id;
			}
		}

		if ( isset( $args['type'] ) && ! empty( $args['type'] ) ) {
			$type = \sanitize_key( $args['type'] ); // Use sanitize_key for consistency
			if ( ! empty( $type ) ) {
				$sql_where[] = "type = %s";
				$sql_params[] = $type;
			}
		}

		if ( isset( $args['is_active'] ) ) {
			$is_active = \boolval( $args['is_active'] ) ? 1 : 0;
			$sql_where[] = "is_active = %d";
			$sql_params[] = $is_active;
		}

		// Build LIMIT clause (optional for now, but included as per instructions)
		if ( isset( $args['number'] ) ) {
			$number = \absint( $args['number'] );
			if ( $number > 0 ) {
				$offset = isset( $args['offset'] ) ? \absint( $args['offset'] ) : 0;
				// IMPORTANT: LIMIT parameters are NOT added to $sql_params for the main prepare call.
				// They are handled separately if needed, or appended directly if safe.
				// For simplicity and safety with prepare, we append it after the main prepare.
				$sql_limit = $this->wpdb->prepare( " LIMIT %d OFFSET %d", $number, $offset );
			}
		}


		// Construct the main query part without LIMIT
		$sql = $sql_select . ' ' . $sql_from;
		if ( ! empty( $sql_where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $sql_where );
		}
		$sql .= ' ' . $sql_orderby; // Add ORDER BY before potential LIMIT

		// Prepare the main query part
		if ( ! empty( $sql_params ) ) {
			$prepared_sql = $this->wpdb->prepare( $sql, $sql_params );
		} else {
			$prepared_sql = $sql; // No parameters to prepare in the main part
		}

		// Append the already prepared LIMIT clause if it exists
		if ( ! empty( $sql_limit ) ) {
			$prepared_sql .= $sql_limit;
		}

		// Execute the query
		$resources = $this->wpdb->get_results( $prepared_sql );

		return is_array( $resources ) ? $resources : [];
	}

	/**
	 * Updates an existing resource.
	 *
	 * @param int   $resource_id The ID of the resource to update.
	 * @param array $data        Associative array of data to update. Allowed keys:
	 *                           'name' (string), 'description' (string), 'type' (string),
	 *                           'capacity' (int), 'is_active' (bool).
	 * @return bool True on success (or if no changes were needed), false on failure or invalid input.
	 */
	public function update_resource( int $resource_id, array $data ): bool {
		$resource_id = \absint( $resource_id );
		if ( $resource_id <= 0 ) {
			error_log( "WP Schedule Plugin: Invalid resource_id ({$resource_id}) provided for update_resource." );
			return false; // Invalid resource ID
		}

		$table_name = $this->get_table_name('resources');
		$update_data = [];
		$update_format = [];

		// Sanitize and prepare data for update
		if ( isset( $data['name'] ) ) {
			$name = \sanitize_text_field( trim( $data['name'] ) );
			if ( ! empty( $name ) ) {
				$update_data['name'] = $name;
				$update_format[] = '%s';
			} else {
				error_log( "WP Schedule Plugin: Attempted to update resource ID {$resource_id} with an empty name." );
				return false; // Name cannot be empty
			}
		}

		if ( isset( $data['description'] ) ) {
			$update_data['description'] = \sanitize_textarea_field( $data['description'] );
			$update_format[] = '%s';
		}

		if ( isset( $data['type'] ) ) {
			$type = \sanitize_key( $data['type'] );
			if ( ! empty( $type ) ) { // Allow empty type? Assuming yes for now. Adjust if needed.
				$update_data['type'] = $type;
				$update_format[] = '%s';
			} else {
				$update_data['type'] = null; // Set to null if cleared
				$update_format[] = null;
			}
		}

		if ( isset( $data['capacity'] ) ) {
			$capacity = \absint( $data['capacity'] );
			// Allow setting capacity to 0 or null? Assuming positive integers or null.
			if ( $capacity >= 0 ) { // Allow 0 capacity
				$update_data['capacity'] = $capacity > 0 ? $capacity : null; // Store 0 as NULL? Or keep 0? Let's keep 0 for now.
				$update_data['capacity'] = $capacity;
				$update_format[] = '%d';
			} else {
                 // Treat negative capacity as invalid? Or ignore? Let's ignore for now.
                 error_log( "WP Schedule Plugin: Invalid (negative) capacity provided for resource ID {$resource_id}." );
            }
		}

		if ( isset( $data['is_active'] ) ) {
			$update_data['is_active'] = \boolval( $data['is_active'] ) ? 1 : 0;
			$update_format[] = '%d';
		}

		// Ignore other keys like org_id or resource_id

		if ( empty( $update_data ) ) {
			return true; // Nothing valid to update, consider it a success.
		}

		// Perform the update
		// Note: updated_at is handled by the DB column definition ON UPDATE CURRENT_TIMESTAMP
		$result = $this->wpdb->update(
			$table_name,
			$update_data,
			[ 'resource_id' => $resource_id ], // WHERE clause
			$update_format,                     // Format for $update_data
			[ '%d' ]                            // Format for WHERE clause
		);

		if ( $result === false ) {
			error_log( "WP Schedule Plugin: Failed to update resource ID {$resource_id}. DB Error: " . $this->wpdb->last_error );
			return false; // Database error during update
		}

		// $result contains the number of rows updated. 0 means no rows changed (data might be the same).
		// We consider 0 rows affected as a success in this context.
		return true;
	}
/**
 * Deletes a resource by its ID.
 *
 * @param int $resource_id The ID of the resource to delete.
 * @return bool True if the resource was deleted or did not exist, false on database error.
 */
public function delete_resource( int $resource_id ): bool {
	$resource_id = \absint( $resource_id ); // Ensure positive integer

	if ( $resource_id <= 0 ) {
		error_log( "WP Schedule Plugin: Invalid resource_id ({$resource_id}) provided for delete_resource." );
		return false; // Invalid resource ID
	}

	$table_name = $this->get_table_name('resources');

	// Perform the deletion
	$result = $this->wpdb->delete(
		$table_name,
		[ 'resource_id' => $resource_id ], // WHERE clause
		[ '%d' ]                            // Format for WHERE clause
	);

	// $this->wpdb->delete() returns the number of rows affected (int >= 0) or false on error.
	if ( $result === false ) {
		error_log( "WP Schedule Plugin: Failed to delete resource ID {$resource_id}. DB Error: " . $this->wpdb->last_error );
		return false; // Database error
	}

	// Return true if deletion succeeded (even if 0 rows were affected because it didn't exist)
	return true;
}

// ... other resource methods like get_organization_resources etc. will go here ...
	// ... other resource methods like delete_resource, get_organization_resources etc. will go here ...

	// --- Shift Methods (Fas 3) ---

	/**
	 * Creates a new shift record.
	 *
	 * @param int    $org_id     The ID of the organization the shift belongs to.
	 * @param string $start_time The start date/time string (e.g., 'YYYY-MM-DD HH:MM:SS').
	 * @param string $end_time   The end date/time string (e.g., 'YYYY-MM-DD HH:MM:SS').
	 * @param array  $data       Optional additional data for the shift:
	 *                           - 'resource_id' (int)
	 *                           - 'user_id' (int)
	 *                           - 'title' (string)
	 *                           - 'notes' (string)
	 *                           - 'status' (string - 'pending', 'confirmed', 'cancelled')
	 * @return int|false The new shift ID on success, false on failure.
	 */
	public function create_shift( int $org_id, string $start_time, string $end_time, array $data = [] ): int|false {
		// 1. Validate required parameters
		$org_id = \absint( $org_id );
		if ( $org_id <= 0 ) {
			error_log( "WP Schedule Plugin: Invalid org_id ({$org_id}) provided for create_shift." );
			return false;
		}

		// Basic validation for date/time strings (can be enhanced)
		// Using strtotime is simple but might accept relative formats we don't want.
		// Using DateTime::createFromFormat is more robust if a specific format is expected.
		$start_timestamp = \strtotime( $start_time );
		$end_timestamp   = \strtotime( $end_time );

		if ( $start_timestamp === false || $end_timestamp === false ) {
			error_log( "WP Schedule Plugin: Invalid start_time ('{$start_time}') or end_time ('{$end_time}') provided for create_shift." );
			return false;
		}
		// Optional: Check if end time is after start time
		if ( $end_timestamp <= $start_timestamp ) {
			error_log( "WP Schedule Plugin: End time ('{$end_time}') must be after start time ('{$start_time}') for create_shift." );
			return false;
		}
		// Format for database insertion (assuming DATETIME column)
		$db_start_time = \gmdate( 'Y-m-d H:i:s', $start_timestamp );
		$db_end_time   = \gmdate( 'Y-m-d H:i:s', $end_timestamp );


		// 2. Get table name
		$table_name = $this->get_table_name('shifts');

		// 3. Prepare base insert data and format
		$insert_data = [
			'org_id'     => $org_id,
			'start_time' => $db_start_time,
			'end_time'   => $db_end_time,
		];
		$format = [ '%d', '%s', '%s' ]; // org_id, start_time, end_time

		// 4. Process optional data
		$allowed_statuses = [ 'pending', 'confirmed', 'cancelled' ];
		$default_status = 'pending';

		if ( isset( $data['resource_id'] ) ) {
			$resource_id = \absint( $data['resource_id'] );
			if ( $resource_id > 0 ) {
				$insert_data['resource_id'] = $resource_id;
				$format[] = '%d';
			}
		}
		if ( isset( $data['user_id'] ) ) {
			$user_id = \absint( $data['user_id'] );
			if ( $user_id > 0 ) {
				$insert_data['user_id'] = $user_id;
				$format[] = '%d';
			}
		}
		if ( isset( $data['title'] ) ) {
			$insert_data['title'] = \sanitize_text_field( $data['title'] );
			$format[] = '%s';
		}
		if ( isset( $data['notes'] ) ) {
			$insert_data['notes'] = \sanitize_textarea_field( $data['notes'] );
			$format[] = '%s';
		}
		if ( isset( $data['status'] ) ) {
			$status = \sanitize_key( $data['status'] );
			if ( in_array( $status, $allowed_statuses, true ) ) {
				$insert_data['status'] = $status;
			} else {
				// Invalid status provided, log warning and use default? Or reject?
				error_log( "WP Schedule Plugin: Invalid status '{$status}' provided for create_shift. Using default '{$default_status}'." );
				$insert_data['status'] = $default_status; // Use default if invalid status given
			}
			$format[] = '%s';
		} else {
			// Add default status if not provided
			$insert_data['status'] = $default_status;
			$format[] = '%s';
		}

		// Note: created_at and updated_at are handled by DB defaults

		// 5. Insert into database
		$result = $this->wpdb->insert( $table_name, $insert_data, $format );

		// 6. Handle result
		if ( $result === false ) {
			error_log( "WP Schedule Plugin: Failed to insert shift for org {$org_id}. DB Error: " . $this->wpdb->last_error );
			return false;
		}

		return $this->wpdb->insert_id; // Return the new shift_id
	}

	/**
	 * Retrieves a single shift by its ID.
	 *
	 * @param int $shift_id The shift ID.
	 * @return object|null The shift object (stdClass) or null if not found or invalid ID.
	 */
	public function get_shift( int $shift_id ): ?object {
		$shift_id = \absint( $shift_id ); // Ensure positive integer

		if ( $shift_id <= 0 ) {
			return null; // Invalid ID
		}

		$table_name = $this->get_table_name('shifts');

		// Prepare the SQL query
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE shift_id = %d",
			$shift_id
		);

		// Execute the query
		$shift = $this->wpdb->get_row( $sql );

		return $shift; // Returns the object or null if no row found
	}

	/**
		* Retrieves multiple shifts based on specified arguments.
		*
		* @param array $args Optional arguments for filtering:
		*                    - 'org_id' (int): Filter by organization ID.
		*                    - 'resource_id' (int): Filter by resource ID.
		*                    - 'user_id' (int): Filter by user ID.
		*                    - 'status' (string): Filter by status (e.g., 'pending', 'confirmed').
		*                    - 'start_date' (string): Filter shifts starting on or after this date ('YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS').
		*                    - 'end_date' (string): Filter shifts ending on or before this date ('YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS').
		*                    - 'number' (int): Number of shifts to retrieve (for pagination).
		*                    - 'offset' (int): Offset for pagination.
		* @return array An array of shift objects (stdClass).
		*/
	public function get_shifts( array $args = [] ): array {
		$table_name = $this->get_table_name('shifts');

		$where_clauses = [];
		$sql_params = [];

		// Base SQL query
		$sql = "SELECT * FROM {$table_name}";

		// Build WHERE conditions based on $args
		if ( isset( $args['org_id'] ) ) {
			$org_id = \absint( $args['org_id'] );
			if ( $org_id > 0 ) {
				$where_clauses[] = "org_id = %d";
				$sql_params[] = $org_id;
			}
		}

		if ( isset( $args['resource_id'] ) ) {
			$resource_id = \absint( $args['resource_id'] );
			if ( $resource_id > 0 ) {
				$where_clauses[] = "resource_id = %d";
				$sql_params[] = $resource_id;
			}
		}

		if ( isset( $args['user_id'] ) ) {
			$user_id = \absint( $args['user_id'] );
			if ( $user_id > 0 ) {
				$where_clauses[] = "user_id = %d";
				$sql_params[] = $user_id;
			}
		}

		if ( isset( $args['status'] ) && ! empty( $args['status'] ) ) {
			$status = \sanitize_key( $args['status'] );
			// Optional: Validate against allowed statuses if needed
			$where_clauses[] = "status = %s";
			$sql_params[] = $status;
		}

		// Handle date range filtering
		if ( isset( $args['start_date'] ) ) {
			$start_timestamp = \strtotime( $args['start_date'] );
			if ( $start_timestamp !== false ) {
				$db_start_date = \gmdate( 'Y-m-d H:i:s', $start_timestamp );
				$where_clauses[] = "start_time >= %s";
				$sql_params[] = $db_start_date;
			}
		}

		if ( isset( $args['end_date'] ) ) {
			// Adjust end_date to include the whole day if only date is provided
			$end_date_str = $args['end_date'];
			if ( strlen($end_date_str) === 10 ) { // Looks like 'YYYY-MM-DD'
				$end_date_str .= ' 23:59:59'; // Include the entire end day
			}
			$end_timestamp = \strtotime( $end_date_str );
			if ( $end_timestamp !== false ) {
				$db_end_date = \gmdate( 'Y-m-d H:i:s', $end_timestamp );
				$where_clauses[] = "end_time <= %s"; // Use end_time for filtering end date
				$sql_params[] = $db_end_date;
			}
		}


		// Append WHERE clause if needed
		if ( ! empty( $where_clauses ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Prepare the query safely
		if ( ! empty( $sql_params ) ) {
			$prepared_sql = $this->wpdb->prepare( $sql, $sql_params );
		} else {
			$prepared_sql = $sql; // No parameters to prepare
		}

		// Add ORDER BY
		$prepared_sql .= " ORDER BY start_time ASC";

		// Handle LIMIT and OFFSET (Optional for now, as per instructions)
		// if ( isset( $args['number'] ) ) {
		// 	$number = \absint( $args['number'] );
		// 	if ( $number > 0 ) {
		// 		$offset = isset( $args['offset'] ) ? \absint( $args['offset'] ) : 0;
		// 		// IMPORTANT: LIMIT parameters need separate handling with prepare or careful appending.
		// 		// Appending directly after the main prepare is often safer.
		// 		$prepared_sql .= $this->wpdb->prepare( " LIMIT %d OFFSET %d", $number, $offset );
		// 	}
		// }


		// Execute the query
		$shifts = $this->wpdb->get_results( $prepared_sql );

		// Return results or empty array
		return is_array( $shifts ) ? $shifts : [];
	}

	/**
	 * Updates an existing shift record.
	 *
	 * @param int   $shift_id The ID of the shift to update.
	 * @param array $data     Associative array of data to update. Allowed keys:
	 *                        'start_time', 'end_time', 'resource_id', 'user_id',
	 *                        'title', 'notes', 'status'.
	 * @return bool True on success (or if no changes were needed), false on failure or invalid input.
	 */
	public function update_shift( int $shift_id, array $data ): bool {
		// 1. Validate shift_id
		$shift_id = \absint( $shift_id );
		if ( $shift_id <= 0 ) {
			error_log( "WP Schedule Plugin: Invalid shift_id ({$shift_id}) provided for update_shift." );
			return false;
		}

		// 2. Get table name
		$table_name = $this->get_table_name('shifts');

		// 3. Initialize update arrays
		$update_data = [];
		$update_format = [];
		$allowed_statuses = [ 'pending', 'confirmed', 'cancelled' ]; // Define allowed statuses

		// 4. Iterate over data and prepare for update
		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'start_time':
				case 'end_time':
					// Validate date/time string
					$timestamp = \strtotime( $value );
					if ( $timestamp !== false ) {
						$db_time = \gmdate( 'Y-m-d H:i:s', $timestamp );
						// Optional: Add validation to ensure end_time > start_time if both are updated?
						$update_data[ $key ] = $db_time;
						$update_format[] = '%s';
					} else {
						error_log( "WP Schedule Plugin: Invalid date/time value '{$value}' for key '{$key}' in update_shift (shift_id: {$shift_id})." );
						// Decide whether to return false or just skip this field
					}
					break;

				case 'resource_id':
				case 'user_id':
					if ( $value === null || $value === '' || $value === 0 || $value === '0' ) {
						$update_data[ $key ] = null;
						$update_format[] = null; // Let wpdb handle null format
					} else {
						$id = \absint( $value );
						if ( $id > 0 ) {
							$update_data[ $key ] = $id;
							$update_format[] = '%d';
						} else {
	                            error_log( "WP Schedule Plugin: Invalid ID value '{$value}' for key '{$key}' in update_shift (shift_id: {$shift_id})." );
	                            // Decide whether to return false or just skip this field
	                       }
					}
					break;

				case 'title':
					$update_data['title'] = \sanitize_text_field( $value );
					$update_format[] = '%s';
					break;

				case 'notes':
					$update_data['notes'] = \sanitize_textarea_field( $value );
					$update_format[] = '%s';
					break;

				case 'status':
					$status = \sanitize_key( $value );
					if ( in_array( $status, $allowed_statuses, true ) ) {
						$update_data['status'] = $status;
						$update_format[] = '%s';
					} else {
						error_log( "WP Schedule Plugin: Invalid status '{$status}' provided for update_shift (shift_id: {$shift_id})." );
						// Decide whether to return false or just skip this field
					}
					break;

				// Ignore other keys like org_id, shift_id, created_at, updated_at
				default:
					break;
			}
		}

		// 5. Check if there's anything to update
		if ( empty( $update_data ) ) {
			return true; // Nothing valid to update, consider it a success.
		}

		// 6. Perform the update
		// Note: updated_at is handled by the DB column definition ON UPDATE CURRENT_TIMESTAMP
		$result = $this->wpdb->update(
			$table_name,
			$update_data,
			[ 'shift_id' => $shift_id ], // WHERE clause
			$update_format,              // Format for $update_data
			[ '%d' ]                     // Format for WHERE clause
		);

		// 7. Handle result
		if ( $result === false ) {
			error_log( "WP Schedule Plugin: Failed to update shift ID {$shift_id}. DB Error: " . $this->wpdb->last_error );
			return false; // Database error during update
		}

		// $result contains the number of rows updated. 0 means no rows changed (data might be the same).
		// We consider 0 rows affected as a success in this context.
		return true;
	}

	/**
	 * Deletes a shift record by its ID.
	 *
	 * @param int $shift_id The ID of the shift to delete.
	 * @return bool True if the shift was deleted or did not exist, false on database error.
	 */
	public function delete_shift( int $shift_id ): bool {
		// 1. Validate shift_id
		$shift_id = \absint( $shift_id );
		if ( $shift_id <= 0 ) {
			error_log( "WP Schedule Plugin: Invalid shift_id ({$shift_id}) provided for delete_shift." );
			return false;
		}

		// 2. Get table name
		$table_name = $this->get_table_name('shifts');

		// 3. Perform the deletion
		$result = $this->wpdb->delete(
			$table_name,
			[ 'shift_id' => $shift_id ], // WHERE clause
			[ '%d' ]                     // Format for WHERE clause
		);

		// 4. Handle result
		// $this->wpdb->delete() returns the number of rows affected (int >= 0) or false on error.
		if ( $result === false ) {
			error_log( "WP Schedule Plugin: Failed to delete shift ID {$shift_id}. DB Error: " . $this->wpdb->last_error );
			return false; // Database error
		}

		// Return true if deletion succeeded (even if 0 rows were affected because it didn't exist)
		return true;
	}

	// ... other shift methods will go here ...
}