<?php
/**
 * Admin page functionality.
 */

namespace JohanBeijer\WPSchedule\Admin;

/**
 * Bootstrap admin functionality.
 */
function bootstrap(): void {
    \add_action('admin_menu', __NAMESPACE__ . '\register_admin_page');
    \add_action('admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_admin_assets');
    \add_action('wp_ajax_wp_schedule_save_data', __NAMESPACE__ . '\handle_save_schedule_data');
}

/**
 * Register the admin menu page.
 */
function register_admin_page(): void {
    \add_menu_page(
        'Schemaläggning',
        'Schemaläggning',
        'manage_options',
        'wp-schedule-admin',
        __NAMESPACE__ . '\render_admin_page',
        'dashicons-calendar-alt'
    );
}

/**
 * Enqueue admin assets.
 *
 * @param string $hook_suffix The hook suffix for the current admin page.
 */
function enqueue_admin_assets(string $hook_suffix): void {
    // Only load on our specific admin page
    if ($hook_suffix !== 'toplevel_page_wp-schedule-admin') {
        return;
    }

    $script_handle = 'wp-schedule-admin-script';

    \Kucrut\Vite\enqueue_asset(
        dirname(__DIR__) . '/app/dist',
        'app/src/admin.js',
        ['handle' => $script_handle]
    );

    // Prepare data for the Svelte app
    // Prepare data using the standard wpApiSettings structure
    // Get REST URL and log it for debugging
    $rest_url_root = \get_rest_url();
    error_log('WP Schedule Plugin: REST API Root URL: ' . $rest_url_root); // DEBUG

    // Prepare data using the standard wpApiSettings structure
    $api_settings = [
        'root'        => \esc_url_raw( $rest_url_root ), // Use the fetched root URL
        'nonce'       => \wp_create_nonce( 'wp_rest' ),
        'userLocale'  => \get_user_locale(),
        // Add any other custom data needed by the app initially
        // 'plugin_namespace' => 'wp-schedule-plugin/v1', // Could pass this if needed, but apiFetch path should handle it
    ];

    // Localize the script with the data object using the standard name
    \wp_localize_script(
        $script_handle,
        'wpApiSettings', // Standard object name for api-fetch
        $api_settings
    );
}

/**
 * Render the admin page content.
 */
function render_admin_page(): void {
    ?>
    <div class="wrap">
        <h1>Schemaläggning</h1>
        <div id="wp-schedule-admin-app"></div>
    </div>
    <?php
}

/**
 * Handles the AJAX request to save schedule data.
 */
function handle_save_schedule_data(): void {
    // Verify nonce
    if (!isset($_POST['nonce']) || !\wp_verify_nonce(\sanitize_text_field(\wp_unslash($_POST['nonce'])), 'wp_schedule_admin_nonce')) {
        \wp_send_json_error(['message' => 'Säkerhetsverifiering misslyckades.'], 403);
        return;
    }

    // Check user permissions
    if (!\current_user_can('manage_options')) {
        \wp_send_json_error(['message' => 'Otillräckliga behörigheter.'], 403);
        return;
    }

    // Get and validate data
    if (!isset($_POST['scheduleData'])) {
        \wp_send_json_error(['message' => 'Ingen schemaläggningsdata skickades.'], 400);
        return;
    }

    // Decode JSON data
    // Use stripslashes before decoding if magic quotes are potentially enabled (though less common now)
    $schedule_data_json = \wp_unslash($_POST['scheduleData']);
    $schedule_data = json_decode($schedule_data_json, true);

    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        \wp_send_json_error(['message' => 'Ogiltig JSON-data. Error: ' . json_last_error_msg()], 400);
        return;
    }

    // Sanitize the data before saving (important!)
    // This is a basic example; you'll need more specific sanitization based on your data structure
    $sanitized_data = sanitize_schedule_data($schedule_data);

    // Save data to the database - adapt as needed
    $success = save_schedule_data($sanitized_data); // Use placeholder function

    if ($success) {
        \wp_send_json_success(['message' => 'Schemaläggningsdata sparad.']);
    } else {
        \wp_send_json_error(['message' => 'Kunde inte spara data.'], 500);
    }
}

/**
 * Hämta schemaläggningsobjekt (Placeholder)
 *
 * @return array Schemaläggningsobjekt
 */
function get_schedule_items(): array {
    // Replace with actual data retrieval logic, e.g., from WordPress options or custom tables
    // Example: return get_option('wp_schedule_items', []);
    return [];
}

/**
 * Spara schemaläggningsdata (Placeholder)
 *
 * @param array $data Data att spara
 * @return bool Om sparandet lyckades
 */
function save_schedule_data(array $data): bool {
    // Replace with actual data saving logic
    // Example: return update_option('wp_schedule_items', $data);
    // Ensure proper error handling
    return true; // Placeholder success
}

/**
 * Sanitize schedule data (Placeholder/Example)
 *
 * Recursively sanitize array data. Adapt based on expected data types.
 *
 * @param mixed $data Data to sanitize.
 * @return mixed Sanitized data.
 */
function sanitize_schedule_data($data) {
    if (is_array($data)) {
        return array_map(__FUNCTION__, $data);
    } elseif (is_object($data)) {
        // Decide how to handle objects if necessary
        return $data; // Or sanitize properties
    } elseif (is_string($data)) {
        return \sanitize_text_field($data);
    }
    // Handle other types like integers, booleans if needed
    return $data;
}