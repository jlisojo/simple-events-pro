<?php
/**
 * Plugin Name: Simple Events Pro
 * Description: Premium recurring-event features for the Simple Events CPT plugin.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Josh
 * Text Domain: simple-events-pro
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Requires Plugins: simple-events-cpt
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SIMPLE_EVENTS_PRO_VERSION', '0.1.0');
define('SIMPLE_EVENTS_PRO_FILE', __FILE__);
define('SIMPLE_EVENTS_PRO_DIR', plugin_dir_path(__FILE__));

add_action('plugins_loaded', 'simple_events_pro_init', 20);
register_activation_hook(__FILE__, 'simple_events_pro_activate');
register_deactivation_hook(__FILE__, 'simple_events_pro_deactivate');

/**
 * Bootstrap Pro features after the free plugin has loaded.
 */
function simple_events_pro_init() {
    if (!class_exists('Simple_Events_Helpers')) {
        add_action('admin_notices', 'simple_events_pro_missing_core_notice');
        return;
    }

    load_plugin_textdomain('simple-events-pro', false, dirname(plugin_basename(SIMPLE_EVENTS_PRO_FILE)) . '/languages');

    require_once SIMPLE_EVENTS_PRO_DIR . 'includes/class-recurrence.php';
    new Simple_Events_Pro_Recurrence();
}

/**
 * Schedule a daily refresh of recurring-event dates.
 */
function simple_events_pro_activate() {
    if (!wp_next_scheduled('simple_events_pro_refresh_occurrences')) {
        wp_schedule_event(time(), 'daily', 'simple_events_pro_refresh_occurrences');
    }
}

/**
 * Remove the scheduled refresh when Pro is deactivated.
 */
function simple_events_pro_deactivate() {
    $timestamp = wp_next_scheduled('simple_events_pro_refresh_occurrences');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'simple_events_pro_refresh_occurrences');
    }
}

/**
 * Inform administrators when the required free plugin is not active.
 */
function simple_events_pro_missing_core_notice() {
    if (!current_user_can('activate_plugins')) {
        return;
    }
    ?>
    <div class="notice notice-error"><p><?php esc_html_e('Simple Events Pro requires the Simple Events CPT plugin to be installed and active.', 'simple-events-pro'); ?></p></div>
    <?php
}