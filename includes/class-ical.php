<?php
/**
 * iCalendar export for Simple Events Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Events_Pro_iCal {

    /**
     * Generate RFC 5545 iCalendar format for an event.
     *
     * @param int $post_id Event post ID.
     * @return string iCalendar text.
     */
    public static function generate_event_ical($post_id) {
        $event = Simple_Events_Helpers::get_event($post_id);
        $post = get_post($post_id);

        if (!$post) {
            return '';
        }

        $uid = sanitize_key($post->post_name) . '-' . $post_id . '@' . wp_parse_url(home_url(), PHP_URL_HOST);
        $dtstart = self::format_ical_date($event['date'], $event['time']);
        $dtend = self::format_ical_date($event['end_date'] ?: $event['date'], $event['end_time']);
        $dtstamp = self::format_ical_date(current_time('Y-m-d'), current_time('H:i:s'));

        $summary = $post->post_title;
        $description = $post->post_content;
        $location = Simple_Events_Helpers::format_location($event);
        $url = get_permalink($post_id);

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Simple Events Pro//WordPress//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:$uid\r\n";
        $ical .= "DTSTAMP:$dtstamp\r\n";
        $ical .= "DTSTART:$dtstart\r\n";
        if ($dtend && $dtend !== $dtstart) {
            $ical .= "DTEND:$dtend\r\n";
        }
        $ical .= "SUMMARY:" . self::escape_ical_text($summary) . "\r\n";

        if ($description) {
            $ical .= "DESCRIPTION:" . self::escape_ical_text(wp_strip_all_tags($description)) . "\r\n";
        }

        if ($location) {
            $ical .= "LOCATION:" . self::escape_ical_text($location) . "\r\n";
        }

        $ical .= "URL:$url\r\n";

        if (!empty($event['registration_link'])) {
            $ical .= "ATTACH:$event[registration_link]\r\n";
        }

        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Generate RFC 5545 iCalendar for a set of events.
     *
     * @param array $event_ids Post IDs.
     * @param string $title Calendar title.
     * @return string iCalendar text.
     */
    public static function generate_events_ical($event_ids, $title = '') {
        if (!$title) {
            $title = get_bloginfo('name') . ' Events';
        }

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Simple Events Pro//WordPress//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:" . self::escape_ical_text($title) . "\r\n";
        $ical .= "X-WR-TIMEZONE:" . self::get_wp_timezone_string() . "\r\n";

        foreach ($event_ids as $post_id) {
            $event = Simple_Events_Helpers::get_event($post_id);
            $post = get_post($post_id);

            if (!$post) {
                continue;
            }

            $uid = sanitize_key($post->post_name) . '-' . $post_id . '@' . wp_parse_url(home_url(), PHP_URL_HOST);
            $dtstart = self::format_ical_date($event['date'], $event['time']);
            $dtend = self::format_ical_date($event['end_date'] ?: $event['date'], $event['end_time']);
            $dtstamp = self::format_ical_date(current_time('Y-m-d'), current_time('H:i:s'));

            $summary = $post->post_title;
            $description = $post->post_content;
            $location = Simple_Events_Helpers::format_location($event);
            $url = get_permalink($post_id);

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:$uid\r\n";
            $ical .= "DTSTAMP:$dtstamp\r\n";
            $ical .= "DTSTART:$dtstart\r\n";
            if ($dtend && $dtend !== $dtstart) {
                $ical .= "DTEND:$dtend\r\n";
            }
            $ical .= "SUMMARY:" . self::escape_ical_text($summary) . "\r\n";

            if ($description) {
                $ical .= "DESCRIPTION:" . self::escape_ical_text(wp_strip_all_tags($description)) . "\r\n";
            }

            if ($location) {
                $ical .= "LOCATION:" . self::escape_ical_text($location) . "\r\n";
            }

            $ical .= "URL:$url\r\n";

            if (!empty($event['registration_link'])) {
                $ical .= "ATTACH:" . esc_url($event['registration_link']) . "\r\n";
            }

            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Format a date and optional time for iCalendar (RFC 5545).
     *
     * @param string $date Date in Y-m-d format.
     * @param string $time Optional time in H:i format.
     * @return string Formatted iCalendar date or datetime.
     */
    private static function format_ical_date($date, $time = '') {
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return '';
        }

        $date = str_replace('-', '', $date);

        if (!$time || !preg_match('/^\d{2}:\d{2}/', $time)) {
            return $date;
        }

        $time = str_replace(':', '', substr($time, 0, 5));
        return $date . 'T' . $time . '00Z';
    }

    /**
     * Escape text for iCalendar format per RFC 5545.
     *
     * @param string $text Text to escape.
     * @return string Escaped text.
     */
    private static function escape_ical_text($text) {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\r\n", '\\n', $text);
        $text = str_replace("\n", '\\n', $text);
        return $text;
    }

    /**
     * Get WordPress timezone string.
     *
     * @return string Timezone identifier.
     */
    private static function get_wp_timezone_string() {
        $tz = get_option('timezone_string');
        return $tz ?: 'UTC';
    }

    /**
     * Get the URL for subscribing to all upcoming events.
     *
     * @return string Calendar subscription URL.
     */
    public static function get_subscription_url() {
        return add_query_arg('se_pro_ical_feed', '1', home_url());
    }

    /**
     * Get the download URL for a single event.
     *
     * @param int $post_id Event post ID.
     * @return string Download URL.
     */
    public static function get_event_download_url($post_id) {
        return add_query_arg(
            array('se_pro_ical_download' => $post_id),
            home_url()
        );
    }
}
