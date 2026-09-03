<?php
/**
 * Calendar view and generation for Simple Events Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Events_Pro_Calendar {

    /**
     * Generate a calendar month structure.
     *
     * @param string $date A date string within the desired month (Y-m-d).
     * @param array  $args Optional query overrides.
     * @return array Month calendar data keyed by week, then by day.
     */
    public static function generate_month($date, $args = array()) {
        try {
            $dt = new DateTime($date);
        } catch (Exception $e) {
            return array();
        }

        $month = (int) $dt->format('m');
        $year = (int) $dt->format('Y');
        $first_day = new DateTime("$year-$month-01");
        $last_day = new DateTime("$year-$month-" . $first_day->format('t'));

        $start_weekday = (int) $first_day->format('w');
        $end_weekday = (int) $last_day->format('w');

        $weeks = array();
        $current_week = array_fill(0, $start_weekday, null);
        $current_date = clone $first_day;

        while ($current_date <= $last_day) {
            $day_index = (int) $current_date->format('w');
            $date_str = $current_date->format('Y-m-d');

            $current_week[$day_index] = array(
                'date' => $date_str,
                'day' => (int) $current_date->format('d'),
                'events' => self::get_day_events($date_str, $args),
            );

            if ($day_index === 6 || $current_date->format('Y-m-d') === $last_day->format('Y-m-d')) {
                while (count($current_week) < 7) {
                    $current_week[] = null;
                }
                $weeks[] = $current_week;
                $current_week = array();
            }

            $current_date->modify('+1 day');
        }

        return array(
            'month' => $month,
            'year' => $year,
            'weeks' => $weeks,
            'month_name' => $first_day->format('F'),
            'event_count' => self::count_month_events($weeks),
        );
    }

    /**
     * Get events for a single day.
     *
     * @param string $date Day in Y-m-d format.
     * @param array  $args Optional query overrides.
     * @return int[] Post IDs of events on this day.
     */
    public static function get_day_events($date, $args = array()) {
        $query_args = array(
            'post_type'      => Simple_Events_Helpers::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_se_event_date',
                    'value'   => $date,
                    'compare' => '=',
                    'type'    => 'DATE',
                ),
            ),
        );

        $query_args = wp_parse_args($args, $query_args);
        $query = new WP_Query($query_args);
        wp_reset_postdata();

        return $query->posts;
    }

    /**
     * Count events across all weeks in a month.
     *
     * @param array $weeks Weeks array.
     * @return int
     */
    private static function count_month_events($weeks) {
        $count = 0;
        foreach ($weeks as $week) {
            foreach ($week as $day) {
                if ($day && !empty($day['events'])) {
                    $count += count($day['events']);
                }
            }
        }
        return $count;
    }

    /**
     * Render a calendar month view.
     *
     * @param string $date Month date (Y-m-d).
     * @param array  $args Optional query or display args.
     */
    public static function render_calendar_month($date, $args = array()) {
        $calendar = self::generate_month($date, $args);
        if (empty($calendar)) {
            return;
        }

        $prev_date = date('Y-m-d', strtotime($calendar['year'] . '-' . str_pad($calendar['month'], 2, '0', STR_PAD_LEFT) . '-01 -1 month'));
        $next_date = date('Y-m-d', strtotime($calendar['year'] . '-' . str_pad($calendar['month'], 2, '0', STR_PAD_LEFT) . '-01 +1 month'));
        $today = current_time('Y-m-d');

        ?>
        <div class="se-pro-calendar">
            <div class="se-pro-calendar__nav">
                <a href="#" class="se-pro-calendar__prev" data-date="<?php echo esc_attr($prev_date); ?>" title="<?php esc_attr_e('Previous month', 'simple-events-pro'); ?>">←</a>
                <h2 class="se-pro-calendar__title"><?php echo esc_html($calendar['month_name'] . ' ' . $calendar['year']); ?></h2>
                <a href="#" class="se-pro-calendar__next" data-date="<?php echo esc_attr($next_date); ?>" title="<?php esc_attr_e('Next month', 'simple-events-pro'); ?>">→</a>
            </div>

            <table class="se-pro-calendar__table">
                <thead>
                    <tr>
                        <?php foreach (array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') as $day) : ?>
                            <th><?php echo esc_html_x($day[0], 'Day of week abbreviation', 'simple-events-pro'); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calendar['weeks'] as $week) : ?>
                        <tr>
                            <?php foreach ($week as $day) : ?>
                                <?php
                                $cell_classes = 'se-pro-calendar__day';
                                if (!$day) {
                                    $cell_classes .= ' se-pro-calendar__day--empty';
                                    echo '<td class="' . esc_attr($cell_classes) . '"></td>';
                                    continue;
                                }
                                if ($day['date'] === $today) {
                                    $cell_classes .= ' se-pro-calendar__day--today';
                                }
                                if (!empty($day['events'])) {
                                    $cell_classes .= ' se-pro-calendar__day--has-events';
                                }
                                ?>
                                <td class="<?php echo esc_attr($cell_classes); ?>" data-date="<?php echo esc_attr($day['date']); ?>">
                                    <div class="se-pro-calendar__day-number"><?php echo esc_html($day['day']); ?></div>
                                    <?php if (!empty($day['events'])) : ?>
                                        <div class="se-pro-calendar__events">
                                            <?php foreach ($day['events'] as $event_id) : ?>
                                                <a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="se-pro-calendar__event" title="<?php echo esc_attr(get_the_title($event_id)); ?>">
                                                    <span class="se-pro-calendar__event-title"><?php echo esc_html(get_the_title($event_id)); ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Get upcoming events for a date range.
     *
     * @param string $start Start date (Y-m-d).
     * @param string $end   End date (Y-m-d).
     * @param array  $args  Query args.
     * @return WP_Query
     */
    public static function get_events_between($start, $end, $args = array()) {
        $defaults = array(
            'post_type'      => Simple_Events_Helpers::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_se_event_date',
                    'value'   => array($start, $end),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ),
            ),
        );

        return new WP_Query(wp_parse_args($args, $defaults));
    }
}
