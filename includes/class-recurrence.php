<?php
/**
 * Recurrence controls for Simple Events Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Events_Pro_Recurrence {

    const META_FREQUENCY = '_se_pro_recurrence_frequency';
    const META_INTERVAL = '_se_pro_recurrence_interval';
    const META_END_DATE = '_se_pro_recurrence_end_date';
    const META_START_DATE = '_se_pro_recurrence_start_date';
    const META_EXCEPTIONS = '_se_pro_recurrence_exceptions';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post_' . Simple_Events_Helpers::POST_TYPE, array($this, 'save_meta_box'));
        add_action('simple_events_pro_refresh_occurrences', array($this, 'refresh_occurrences'));
        add_action('wp', array($this, 'refresh_due_occurrences'));
        add_action('add_meta_boxes', array($this, 'add_exceptions_meta_box'));
        add_action('save_post_' . Simple_Events_Helpers::POST_TYPE, array($this, 'save_exceptions_meta_box'));
    }

    /**
     * Add recurrence settings to the event editor.
     */
    public function add_meta_box() {
        add_meta_box(
            'se_pro_recurrence',
            __('Recurring Event', 'simple-events-pro'),
            array($this, 'render_meta_box'),
            Simple_Events_Helpers::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Render recurrence controls.
     *
     * @param WP_Post $post Event post.
     */
    public function render_meta_box($post) {
        wp_nonce_field('save_se_pro_recurrence', 'se_pro_recurrence_nonce');

        $frequency = get_post_meta($post->ID, self::META_FREQUENCY, true);
        $interval = absint(get_post_meta($post->ID, self::META_INTERVAL, true));
        $end_date = get_post_meta($post->ID, self::META_END_DATE, true);
        ?>
        <p>
            <label for="se_pro_recurrence_frequency"><?php esc_html_e('Repeats', 'simple-events-pro'); ?></label>
            <select id="se_pro_recurrence_frequency" name="se_pro_recurrence_frequency">
                <option value="none" <?php selected($frequency, 'none'); ?>><?php esc_html_e('Does not repeat', 'simple-events-pro'); ?></option>
                <option value="daily" <?php selected($frequency, 'daily'); ?>><?php esc_html_e('Daily', 'simple-events-pro'); ?></option>
                <option value="weekly" <?php selected($frequency, 'weekly'); ?>><?php esc_html_e('Weekly', 'simple-events-pro'); ?></option>
                <option value="monthly" <?php selected($frequency, 'monthly'); ?>><?php esc_html_e('Monthly', 'simple-events-pro'); ?></option>
            </select>
        </p>
        <p>
            <label for="se_pro_recurrence_interval"><?php esc_html_e('Repeat every', 'simple-events-pro'); ?></label>
            <input type="number" id="se_pro_recurrence_interval" name="se_pro_recurrence_interval" value="<?php echo esc_attr($interval ? $interval : 1); ?>" min="1" max="365" />
        </p>
        <p>
            <label for="se_pro_recurrence_end_date"><?php esc_html_e('Repeat until', 'simple-events-pro'); ?></label>
            <input type="date" id="se_pro_recurrence_end_date" name="se_pro_recurrence_end_date" value="<?php echo esc_attr($end_date); ?>" />
        </p>
        <?php
    }

    /**
     * Store recurrence settings for an event.
     *
     * @param int $post_id Event post ID.
     */
    public function save_meta_box($post_id) {
        if (!isset($_POST['se_pro_recurrence_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['se_pro_recurrence_nonce'])), 'save_se_pro_recurrence')) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
            return;
        }

        $frequencies = array('none', 'daily', 'weekly', 'monthly');
        $frequency = isset($_POST['se_pro_recurrence_frequency']) ? sanitize_key(wp_unslash($_POST['se_pro_recurrence_frequency'])) : 'none';
        $frequency = in_array($frequency, $frequencies, true) ? $frequency : 'none';
        $interval = isset($_POST['se_pro_recurrence_interval']) ? min(365, max(1, absint($_POST['se_pro_recurrence_interval']))) : 1;
        $end_date = isset($_POST['se_pro_recurrence_end_date']) ? sanitize_text_field(wp_unslash($_POST['se_pro_recurrence_end_date'])) : '';
        $previous_frequency = get_post_meta($post_id, self::META_FREQUENCY, true);
        $start_date = get_post_meta($post_id, self::META_START_DATE, true);
        if (!$start_date || $previous_frequency === 'none') {
            $start_date = Simple_Events_Helpers::meta($post_id, 'date');
        }

        update_post_meta($post_id, self::META_FREQUENCY, $frequency);
        update_post_meta($post_id, self::META_INTERVAL, $interval);
        update_post_meta($post_id, self::META_END_DATE, $end_date);

        if ($frequency === 'none') {
            if ($start_date) {
                update_post_meta($post_id, '_se_event_date', $start_date);
            }
            delete_post_meta($post_id, self::META_START_DATE);
            return;
        }

        update_post_meta($post_id, self::META_START_DATE, $start_date);
        $this->refresh_occurrence($post_id);
    }

    /**
     * Add exception editor to the event editor.
     */
    public function add_exceptions_meta_box() {
        $post_id = get_the_ID();
        $frequency = get_post_meta($post_id, self::META_FREQUENCY, true);
        if ($frequency === 'none' || !$frequency) {
            return;
        }

        add_meta_box(
            'se_pro_exceptions',
            __('Occurrence Exceptions', 'simple-events-pro'),
            array($this, 'render_exceptions_meta_box'),
            Simple_Events_Helpers::POST_TYPE,
            'side',
            'low'
        );
    }

    /**
     * Render exception editor.
     *
     * @param WP_Post $post Event post.
     */
    public function render_exceptions_meta_box($post) {
        wp_nonce_field('save_se_pro_exceptions', 'se_pro_exceptions_nonce');

        $exceptions = self::get_exceptions($post->ID);
        ?>
        <p><small><?php esc_html_e('Add a date to skip it, or reschedule it to a new date.', 'simple-events-pro'); ?></small></p>
        <div id="se-pro-exceptions-list">
            <?php foreach ($exceptions as $original_date => $exception) : ?>
                <div class="se-pro-exception" style="border-bottom: 1px solid #eee; padding: 10px 0;">
                    <p style="margin: 0;">
                        <strong><?php echo esc_html($original_date); ?></strong>
                    </p>
                    <p style="margin: 5px 0;">
                        <?php if ($exception['type'] === 'skip') : ?>
                            <span style="color: #999;"><?php esc_html_e('Skip this occurrence', 'simple-events-pro'); ?></span>
                        <?php else : ?>
                            <?php esc_html_e('Reschedule to:', 'simple-events-pro'); ?>
                            <strong><?php echo esc_html($exception['new_date']); ?></strong>
                        <?php endif; ?>
                    </p>
                    <p style="margin: 5px 0;">
                        <button class="button button-small se-pro-exception-remove" data-date="<?php echo esc_attr($original_date); ?>" type="button">
                            <?php esc_html_e('Remove', 'simple-events-pro'); ?>
                        </button>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
        <p>
            <label for="se_pro_exception_date"><?php esc_html_e('Occurrence date to modify', 'simple-events-pro'); ?></label>
            <input type="date" id="se_pro_exception_date" style="width: 100%; margin-bottom: 5px;" />
        </p>
        <p>
            <label>
                <input type="radio" name="se_pro_exception_action" value="skip" checked />
                <?php esc_html_e('Skip this occurrence', 'simple-events-pro'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="radio" name="se_pro_exception_action" value="reschedule" />
                <?php esc_html_e('Reschedule to:', 'simple-events-pro'); ?>
            </label>
            <input type="date" id="se_pro_exception_new_date" style="width: 100%;" />
        </p>
        <p>
            <button id="se-pro-exception-add" class="button button-primary" type="button">
                <?php esc_html_e('Add Exception', 'simple-events-pro'); ?>
            </button>
        </p>
        <input type="hidden" id="se_pro_exceptions_json" name="se_pro_exceptions_json" value="<?php echo esc_attr(wp_json_encode($exceptions)); ?>" />
        <script>
        (function() {
            var list = document.getElementById('se-pro-exceptions-list');
            var jsonInput = document.getElementById('se_pro_exceptions_json');
            var addBtn = document.getElementById('se-pro-exception-add');
            var dateInput = document.getElementById('se_pro_exception_date');
            var actionRadios = document.querySelectorAll('input[name="se_pro_exception_action"]');
            var newDateInput = document.getElementById('se_pro_exception_new_date');

            function updateJSON() {
                var exceptions = {};
                var rows = list.querySelectorAll('.se-pro-exception');
                rows.forEach(function(row) {
                    var dateEl = row.querySelector('strong');
                    var date = dateEl ? dateEl.textContent : '';
                    if (!date) return;
                    if (row.innerHTML.includes('Skip this occurrence')) {
                        exceptions[date] = { type: 'skip' };
                    } else {
                        var newDateEl = row.querySelectorAll('strong');
                        if (newDateEl[1]) {
                            exceptions[date] = { type: 'reschedule', new_date: newDateEl[1].textContent };
                        }
                    }
                });
                jsonInput.value = JSON.stringify(exceptions);
            }

            addBtn.addEventListener('click', function() {
                if (!dateInput.value) return;
                var action = document.querySelector('input[name="se_pro_exception_action"]:checked').value;
                if (action === 'reschedule' && !newDateInput.value) {
                    alert('<?php esc_attr_e('Please select a new date.', 'simple-events-pro'); ?>');
                    return;
                }

                var div = document.createElement('div');
                div.className = 'se-pro-exception';
                div.style.cssText = 'border-bottom: 1px solid #eee; padding: 10px 0;';
                var html = '<p style="margin: 0;"><strong>' + dateInput.value + '<\/strong><\/p>';
                if (action === 'skip') {
                    html += '<p style="margin: 5px 0;"><span style="color: #999;"><?php esc_html_e('Skip this occurrence', 'simple-events-pro'); ?><\/span><\/p>';
                } else {
                    html += '<p style="margin: 5px 0;"><?php esc_html_e('Reschedule to:', 'simple-events-pro'); ?> <strong>' + newDateInput.value + '<\/strong><\/p>';
                }
                html += '<p style="margin: 5px 0;"><button class="button button-small se-pro-exception-remove" data-date="' + dateInput.value + '" type="button"><?php esc_html_e('Remove', 'simple-events-pro'); ?><\/button><\/p>';
                div.innerHTML = html;
                list.appendChild(div);
                updateJSON();
                dateInput.value = '';
                newDateInput.value = '';
            });

            list.addEventListener('click', function(e) {
                if (e.target.classList.contains('se-pro-exception-remove')) {
                    e.target.closest('.se-pro-exception').remove();
                    updateJSON();
                }
            });
        })();
        </script>
        <?php
    }

    /**
     * Save exception overrides.
     *
     * @param int $post_id Event post ID.
     */
    public function save_exceptions_meta_box($post_id) {
        if (!isset($_POST['se_pro_exceptions_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['se_pro_exceptions_nonce'])), 'save_se_pro_exceptions')) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
            return;
        }

        $json = isset($_POST['se_pro_exceptions_json']) ? sanitize_text_field(wp_unslash($_POST['se_pro_exceptions_json'])) : '{}';
        $exceptions = json_decode($json, true);

        if (!is_array($exceptions)) {
            $exceptions = array();
        }

        $sanitized = array();
        foreach ($exceptions as $date => $exception) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            if (isset($exception['type']) && in_array($exception['type'], array('skip', 'reschedule'), true)) {
                $sanitized[$date] = $exception;
                if ($exception['type'] === 'reschedule' && isset($exception['new_date'])) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $exception['new_date'])) {
                        unset($sanitized[$date]);
                    }
                }
            }
        }

        update_post_meta($post_id, self::META_EXCEPTIONS, $sanitized);
        $this->refresh_occurrence($post_id);
    }

    /**
     * Get all exceptions for an event.
     *
     * @param int $post_id Event post ID.
     * @return array
     */
    public static function get_exceptions($post_id) {
        $exceptions = get_post_meta($post_id, self::META_EXCEPTIONS, true);
        return is_array($exceptions) ? $exceptions : array();
    }

    /**
     * Refresh every recurring event. Used by WP-Cron.
     */
    public function refresh_occurrences() {
        $event_ids = get_posts(array(
            'post_type'      => Simple_Events_Helpers::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => self::META_FREQUENCY,
                    'value'   => 'none',
                    'compare' => '!=',
                ),
            ),
        ));

        foreach ($event_ids as $event_id) {
            $this->refresh_occurrence($event_id);
        }
    }

    /**
     * Refresh only stale events during public requests so dates remain current
     * even when a site has infrequent WP-Cron traffic.
     */
    public function refresh_due_occurrences() {
        $event_ids = get_posts(array(
            'post_type'      => Simple_Events_Helpers::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => self::META_FREQUENCY,
                    'value'   => array('daily', 'weekly', 'monthly'),
                    'compare' => 'IN',
                ),
                array(
                    'key'     => '_se_event_date',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '<',
                    'type'    => 'DATE',
                ),
            ),
        ));

        foreach ($event_ids as $event_id) {
            $this->refresh_occurrence($event_id);
        }
    }

    /**
     * Synchronize an event's free-plugin date with its next recurrence.
     *
     * @param int $post_id Event post ID.
     */
    private function refresh_occurrence($post_id) {
        $frequency = get_post_meta($post_id, self::META_FREQUENCY, true);
        $start_date = get_post_meta($post_id, self::META_START_DATE, true);
        $interval = max(1, absint(get_post_meta($post_id, self::META_INTERVAL, true)));
        $end_date = get_post_meta($post_id, self::META_END_DATE, true);
        $exceptions = self::get_exceptions($post_id);
        $next_date = self::next_occurrence_with_exceptions($start_date, $frequency, $interval, current_time('Y-m-d'), $end_date, $exceptions);

        if ($next_date) {
            update_post_meta($post_id, '_se_event_date', $next_date);
        }
    }

    /**
     * Find the first recurrence on or after the requested date.
     *
     * @param string $start_date Original event date.
     * @param string $frequency  Daily, weekly, or monthly.
     * @param int    $interval   Number of units between occurrences.
     * @param string $from_date  Earliest allowed occurrence.
     * @param string $end_date   Optional inclusive recurrence end date.
     * @return string Empty when no valid occurrence remains.
     */
    public static function next_occurrence($start_date, $frequency, $interval, $from_date, $end_date = '') {
        if (!$start_date || !in_array($frequency, array('daily', 'weekly', 'monthly'), true)) {
            return '';
        }

        try {
            $occurrence = new DateTimeImmutable($start_date, wp_timezone());
            $from = new DateTimeImmutable($from_date, wp_timezone());
        } catch (Exception $exception) {
            return '';
        }

        $interval = max(1, (int) $interval);
        $date_interval = new DateInterval('P' . $interval . ($frequency === 'daily' ? 'D' : ($frequency === 'weekly' ? 'W' : 'M')));

        while ($occurrence < $from) {
            $occurrence = $occurrence->add($date_interval);
        }

        $date = $occurrence->format('Y-m-d');
        return ($end_date && $date > $end_date) ? '' : $date;
    }

    /**
     * Find the next occurrence, skipping exceptions and following rescheduled dates.
     *
     * @param string $start_date Original event date.
     * @param string $frequency  Daily, weekly, or monthly.
     * @param int    $interval   Number of units between occurrences.
     * @param string $from_date  Earliest allowed occurrence.
     * @param string $end_date   Optional inclusive recurrence end date.
     * @param array  $exceptions Exception overrides.
     * @return string Empty when no valid occurrence remains.
     */
    public static function next_occurrence_with_exceptions($start_date, $frequency, $interval, $from_date, $end_date = '', $exceptions = array()) {
        $occurrence = self::next_occurrence($start_date, $frequency, $interval, $from_date, $end_date);

        if (!$occurrence || !is_array($exceptions)) {
            return $occurrence;
        }

        $max_attempts = 365;
        $attempt = 0;

        while ($attempt < $max_attempts) {
            if (!isset($exceptions[$occurrence])) {
                return $occurrence;
            }

            $exception = $exceptions[$occurrence];
            if ($exception['type'] === 'reschedule' && !empty($exception['new_date'])) {
                return $exception['new_date'];
            }

            if ($exception['type'] === 'skip') {
                $next_check = date('Y-m-d', strtotime($occurrence) + 86400);
                $occurrence = self::next_occurrence($start_date, $frequency, $interval, $next_check, $end_date);
                if (!$occurrence) {
                    return '';
                }
            }

            $attempt++;
        }

        return '';
    }
}