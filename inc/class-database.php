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
		// dbDelta() logic will go here in Fas 1, Fas 2, Fas 3...
		require_once \ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $this->wpdb->get_charset_collate();

		// Example (actual tables defined in later phases):
		// $sql_organizations = "CREATE TABLE {$this->get_table_name('organizations')} ( ... ) $charset_collate;";
		// dbDelta( $sql_organizations );

		// Add tables for members, resources, shifts here later.
	}

	// --- Placeholder methods for Fas 1 ---
	// public function create_organization(...) {}
	// public function get_organization(...) {}
	// ... etc ...

	// --- Placeholder methods for Fas 2 ---
	// public function create_resource(...) {}
	// ... etc ...

	// --- Placeholder methods for Fas 3 ---
	// public function create_shift(...) {}
	// ... etc ...
}