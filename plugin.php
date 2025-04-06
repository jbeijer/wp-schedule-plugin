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

	// Instantiate classes and hook API initialization
	$db = new Database();
	$api_handlers = new ApiHandlers($db);
	\add_action('rest_api_init', [$api_handlers, 'register_routes']);


	// Bootstrap frontend and admin
	Frontend\bootstrap();
	Admin\bootstrap();
}
\add_action('plugins_loaded', __NAMESPACE__ . '\\initialize_plugin'); // Default priority 10



/**
 * Plugin activation hook.
 *
 * Creates database tables, adds roles/capabilities.
 */
function wp_schedule_plugin_activate() {
	// Ensure required files are loaded for activation logic if needed
	// require_once WP_SCHEDULE_PLUGIN_PATH . 'inc/class-database.php';
	// $db = new Database();
	// $db->create_or_update_tables(); // Example call

	// Placeholder: Add role/caps logic here

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