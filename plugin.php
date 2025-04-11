<?php
/**
 * Plugin Name: WP Schedule Plugin
 * Description: Ett plugin för schemaläggning byggt med Svelte.
 * Author: Johan Beijer
 * Author URI: https://example.com
 * License: GPLv2
 * Version: 0.0.1
 * Text Domain: wp-schedule-plugin
 * Domain Path: /languages
 */

namespace JohanBeijer\WPSchedule;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Plugin Constants within the namespace
if ( ! defined( 'WP_SCHEDULE_PLUGIN_PATH' ) ) {
	define( 'WP_SCHEDULE_PLUGIN_PATH', \plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WP_SCHEDULE_PLUGIN_URL' ) ) {
	define( 'WP_SCHEDULE_PLUGIN_URL', \plugin_dir_url( __FILE__ ) );
}

/**
 * Initialize the plugin.
 * Loads text domain, checks dependencies, requires files, and bootstraps features.
 */
function initialize_plugin() {
	error_log('WP Schedule Plugin: initialize_plugin() called.'); // DEBUG
	// Load Text Domain first
	\load_plugin_textdomain(
		'wp-schedule-plugin',
		false,
		dirname( \plugin_basename( WP_SCHEDULE_PLUGIN_PATH . 'plugin.php' ) ) . '/languages/' // Use constant
	);

    // Check if composer dependencies are loaded
    if (file_exists( WP_SCHEDULE_PLUGIN_PATH . 'vendor/autoload.php')) { // Use constant
        require_once WP_SCHEDULE_PLUGIN_PATH . 'vendor/autoload.php';
    } else {
        // Optionally add an admin notice if dependencies are missing
        \add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>WP Schedule Plugin:</strong> Composer dependencies not found. Please run <code>composer install</code> in the plugin directory.</p></div>';
        });
        // Prevent further execution if dependencies are critical
        return;
    }
	// Require core files
	require_once WP_SCHEDULE_PLUGIN_PATH . 'inc/frontend.php';
	require_once WP_SCHEDULE_PLUGIN_PATH . 'inc/admin.php';
	// Require class files (check existence for robustness)
	if ( file_exists( WP_SCHEDULE_PLUGIN_PATH . 'inc/class-database.php' ) ) {
		require_once WP_SCHEDULE_PLUGIN_PATH . 'inc/class-database.php';
	}
	if ( file_exists( WP_SCHEDULE_PLUGIN_PATH . 'inc/class-apihandlers.php' ) ) {
		require_once WP_SCHEDULE_PLUGIN_PATH . 'inc/class-apihandlers.php';
	}

	// API routes are registered via the rest_api_init hook below


	// Bootstrap frontend and admin
	Frontend\bootstrap();
	Admin\bootstrap();
}
\add_action('plugins_loaded', __NAMESPACE__ . '\\initialize_plugin');

/**
 * Registers the REST API routes for the plugin.
 */
function register_api_routes() {
	// Ensure classes are loaded (should be by plugins_loaded)
	if ( ! class_exists( __NAMESPACE__ . '\\Database' ) || ! class_exists( __NAMESPACE__ . '\\ApiHandlers' ) ) {
		error_log('WP Schedule Plugin: ERROR - Cannot register API routes, classes not found.'); // DEBUG
		return;
	}
	$db = new Database();
	$api_handlers = new ApiHandlers($db);
	$api_handlers->register_routes(); // Call the registration method directly
	error_log('WP Schedule Plugin: register_api_routes() executed.'); // DEBUG
}
\add_action( 'rest_api_init', __NAMESPACE__ . '\\register_api_routes' );



/**
 * Plugin activation hook.
 *
 * Creates database tables, adds roles/capabilities.
 */
function wp_schedule_plugin_activate() {
	// Ensure Database class file is loaded
	$db_class_file = WP_SCHEDULE_PLUGIN_PATH . 'inc/class-database.php';
	if ( file_exists( $db_class_file ) ) {
		require_once $db_class_file;
	} else {
		// Cannot proceed without the database class
		error_log('WP Schedule Plugin: ERROR - class-database.php not found during activation.');
		return;
	}

	// Create tables
	$db = new Database();
	$db->create_or_update_tables();
	error_log('WP Schedule Plugin: create_or_update_tables() called during activation.'); // DEBUG

	// Add custom capability to administrator role
	$admin_role = \get_role( 'administrator' );
	if ( $admin_role instanceof \WP_Role ) {
		$admin_role->add_cap( 'access_schemaplugin', true );
		error_log('WP Schedule Plugin: Added access_schemaplugin capability to administrator.'); // DEBUG
	} else {
		error_log('WP Schedule Plugin: ERROR - Could not get administrator role to add capability.'); // DEBUG
	}

	// Placeholder: Add schema_user role logic here later if needed

	// Flush rewrite rules to ensure REST API endpoints are recognized
	\flush_rewrite_rules();

	// Set activation flag
	\add_option( 'wp_schedule_plugin_activated', true );
}

/**
 * Plugin deactivation hook.
 *
 * Optional cleanup.
 */
function wp_schedule_plugin_deactivate() {
	// Optional: Remove roles/caps or other cleanup
}

// Register hooks outside the namespace, referencing namespaced functions
// Note: __FILE__ refers to this file (plugin.php)
\register_activation_hook( __FILE__, __NAMESPACE__ . '\\wp_schedule_plugin_activate' );
\register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\wp_schedule_plugin_deactivate' );