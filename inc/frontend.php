<?php

declare(strict_types = 1);

namespace JohanBeijer\WPSchedule\Frontend;

use Kucrut\Vite;

/**
 * Frontend bootstrapper
 */
function bootstrap(): void {
    \add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_script');
    \add_action('wp_footer', __NAMESPACE__ . '\\render_app');
}

/**
 * Render application's markup
 */
function render_app(): void {
    ?>
    <div id="wp-schedule-app" class="wp-schedule-app"></div>
    <?php
}

/**
 * Enqueue script
 */
function enqueue_script(): void {
    Vite\enqueue_asset(
        dirname(__DIR__) . '/app/dist',
        'app/src/main.js',
        [
            'handle' => 'wp-schedule-plugin',
            'in-footer' => true,
        ]
    );

    // Hämta schemaläggningsdatan - anpassa detta efter din datastruktur
    $schedule_data = get_schedule_data();

    // Skicka datan till frontend-skriptet
    \wp_localize_script(
        'wp-schedule-plugin',
        'wpScheduleFrontendData',
        [
            'scheduleData' => $schedule_data,
        ]
    );
}

/**
 * Hämta schemaläggningsdata
 *
 * @return array Schemaläggningsdata
 */
function get_schedule_data(): array {
    // Detta är en platshållare - du behöver ersätta detta med din egen logik
    // som hämtar data från WordPress-databasen
    // Example: Fetching data from WordPress options
    // $data = get_option('wp_schedule_data', ['events' => [], 'settings' => []]);
    // return is_array($data) ? $data : ['events' => [], 'settings' => []];

    return [
        'events' => [],
        'settings' => [],
    ];
}