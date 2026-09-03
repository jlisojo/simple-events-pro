<?php
/**
 * iCalendar download and feed handlers for Simple Events Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Events_Pro_iCal_Handler {

    public function __construct() {
        add_action('init', array($this, 'handle_ical_requests'));
        add_filter('template_redirect', array($this, 'serve_ical_feed'));
        add_action('wp_footer', array($this, 'print_ical_links'));
    }

    /**
     * Handle iCal download requests.
     */
    public function handle_ical_requests() {
        if (isset($_GET['se_pro_ical_download'])) {
            $post_id = absint($_GET['se_pro_ical_download']);
            if (get_post_type($post_id) === Simple_Events_Helpers::POST_TYPE && get_post_status($post_id) === 'publish') {
                $this->serve_event_ical($post_id);
            }
            wp_die();
        }
    }

    /**
     * Serve iCalendar feed for all upcoming events.
     */
    public function serve_ical_feed() {
        if (!isset($_GET['se_pro_ical_feed']) || !$_GET['se_pro_ical_feed']) {
            return;
        }

        $query = new WP_Query(Simple_Events_Helpers::upcoming_query_args(array(
            'posts_per_page' => -1,
        )));

        $event_ids = $query->posts;
        wp_reset_postdata();

        $ical = Simple_Events_Pro_iCal::generate_events_ical($event_ids, __('Upcoming Events', 'simple-events-pro'));

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="events.ics"');
        header('Cache-Control: no-cache, must-revalidate');
        echo $ical;
        wp_die();
    }

    /**
     * Serve iCalendar file for a single event.
     *
     * @param int $post_id Event post ID.
     */
    private function serve_event_ical($post_id) {
        $post = get_post($post_id);
        $ical = Simple_Events_Pro_iCal::generate_event_ical($post_id);
        $filename = sanitize_file_name($post->post_title . '.ics');

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        echo $ical;
        wp_die();
    }

    /**
     * Print iCal feed link in page head for auto-discovery.
     */
    public function print_ical_links() {
        if (is_singular(Simple_Events_Helpers::POST_TYPE)) {
            $post_id = get_the_ID();
            $url = Simple_Events_Pro_iCal::get_event_download_url($post_id);
            echo '<link rel="alternate" type="text/calendar" href="' . esc_url($url) . '" />';
        }
    }
}
