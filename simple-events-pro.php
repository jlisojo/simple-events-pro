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

    require_once SIMPLE_EVENTS_PRO_DIR . 'includes/class-calendar.php';
    require_once SIMPLE_EVENTS_PRO_DIR . 'includes/class-calendar-shortcode.php';
    new Simple_Events_Pro_Calendar_Shortcode();

    require_once SIMPLE_EVENTS_PRO_DIR . 'includes/class-ical.php';
    require_once SIMPLE_EVENTS_PRO_DIR . 'includes/class-ical-handler.php';
    require_once SIMPLE_EVENTS_PRO_DIR . 'includes/class-ical-ui.php';
    new Simple_Events_Pro_iCal_Handler();
    new Simple_Events_Pro_iCal_UI();

    add_shortcode('simple_events_subscribe', 'simple_events_pro_subscribe_shortcode');
}

/**
 * Render a subscription link shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function simple_events_pro_subscribe_shortcode($atts) {
    $url = Simple_Events_Pro_iCal::get_subscription_url();
    return '<a href="' . esc_url($url) . '" class="se-pro-ical-link se-pro-ical-subscribe" title="' . esc_attr__('Subscribe to calendar feed', 'simple-events-pro') . '">' . esc_html__('Subscribe to Events', 'simple-events-pro') . '</a>';
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