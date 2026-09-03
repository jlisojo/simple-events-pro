<?php
/**
 * iCalendar UI elements for Simple Events Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Events_Pro_iCal_UI {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_ical_styles'));
        add_action('the_content', array($this, 'add_event_download_link'));
    }

    /**
     * Enqueue styles for iCal UI.
     */
    public function enqueue_ical_styles() {
        wp_enqueue_style(
            'simple-events-pro-ical',
            plugin_dir_url(SIMPLE_EVENTS_PRO_FILE) . 'assets/css/ical.css',
            array(),
            SIMPLE_EVENTS_PRO_VERSION
        );
    }

    /**
     * Add download link to single event content.
     *
     * @param string $content Post content.
     * @return string Modified content.
     */
    public function add_event_download_link($content) {
        if (!is_singular(Simple_Events_Helpers::POST_TYPE)) {
            return $content;
        }

        $post_id = get_the_ID();
        $download_url = Simple_Events_Pro_iCal::get_event_download_url($post_id);
        $feed_url = Simple_Events_Pro_iCal::get_subscription_url();

        $links = '<div class="se-pro-ical-links">';
        $links .= '<a href="' . esc_url($download_url) . '" class="se-pro-ical-link se-pro-ical-download" download>';
        $links .= esc_html__('Download Event (.ics)', 'simple-events-pro');
        $links .= '</a>';
        $links .= '<a href="' . esc_url($feed_url) . '" class="se-pro-ical-link se-pro-ical-subscribe" title="' . esc_attr__('Add to your calendar app', 'simple-events-pro') . '">';
        $links .= esc_html__('Subscribe to Events', 'simple-events-pro');
        $links .= '</a>';
        $links .= '</div>';

        return $content . $links;
    }

    /**
     * Render iCal download/subscribe button for use in templates.
     *
     * @param int $post_id Event post ID.
     */
    public static function render_ical_button($post_id) {
        $download_url = Simple_Events_Pro_iCal::get_event_download_url($post_id);
        ?>
        <a href="<?php echo esc_url($download_url); ?>" class="se-pro-ical-button" download title="<?php esc_attr_e('Download this event', 'simple-events-pro'); ?>">
            <span class="se-pro-ical-icon">📅</span>
            <?php esc_html_e('Add to Calendar', 'simple-events-pro'); ?>
        </a>
        <?php
    }
}
