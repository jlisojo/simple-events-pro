<?php
/**
 * Calendar shortcode for Simple Events Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Events_Pro_Calendar_Shortcode {

    public function __construct() {
        add_shortcode('simple_events_calendar', array($this, 'render'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_calendar_assets'));
        add_action('wp_footer', array($this, 'print_calendar_js'));
    }

    /**
     * Render the calendar shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render($atts) {
        $atts = shortcode_atts(array(
            'month' => current_time('Y-m-d'),
            'show_filters' => 'true',
        ), $atts, 'simple_events_calendar');

        $month = sanitize_text_field($atts['month']);
        $show_filters = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);

        // Validate the date.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $month)) {
            $month = current_time('Y-m-d');
        }

        ob_start();
        ?>
        <div class="se-pro-calendar-wrapper">
            <?php if ($show_filters) : ?>
                <div class="se-pro-calendar-filters">
                    <label>
                        <?php esc_html_e('Category:', 'simple-events-pro'); ?>
                        <select class="se-pro-calendar-filter-category">
                            <option value=""><?php esc_html_e('All Categories', 'simple-events-pro'); ?></option>
                            <?php
                            $terms = get_terms(array(
                                'taxonomy' => Simple_Events_Helpers::TAX_CATEGORY,
                                'hide_empty' => true,
                            ));
                            foreach ($terms as $term) {
                                echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                </div>
            <?php endif; ?>

            <div class="se-pro-calendar-container" data-month="<?php echo esc_attr($month); ?>">
                <?php Simple_Events_Pro_Calendar::render_calendar_month($month); ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Enqueue calendar CSS.
     */
    public function enqueue_calendar_assets() {
        if (has_shortcode(get_post()->post_content, 'simple_events_calendar')) {
            wp_enqueue_style(
                'simple-events-pro-calendar',
                plugin_dir_url(SIMPLE_EVENTS_PRO_FILE) . 'assets/css/calendar.css',
                array(),
                SIMPLE_EVENTS_PRO_VERSION
            );
        }
    }

    /**
     * Print calendar navigation script.
     */
    public function print_calendar_js() {
        if (!has_shortcode(get_post()->post_content, 'simple_events_calendar')) {
            return;
        }
        ?>
        <script>
        (function() {
            var calendar = document.querySelector('.se-pro-calendar-container');
            if (!calendar) return;

            var prevBtn = document.querySelector('.se-pro-calendar__prev');
            var nextBtn = document.querySelector('.se-pro-calendar__next');

            if (!prevBtn || !nextBtn) return;

            function navigateMonth(date) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var html = xhr.responseText;
                        var tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        var newCalendar = tempDiv.querySelector('.se-pro-calendar');
                        var oldCalendar = document.querySelector('.se-pro-calendar');
                        if (newCalendar && oldCalendar) {
                            oldCalendar.parentNode.replaceChild(newCalendar, oldCalendar);
                            attachCalendarHandlers();
                        }
                    }
                };
                xhr.send('action=simple_events_pro_calendar_month&month=' + encodeURIComponent(date) + '&nonce=<?php echo esc_attr(wp_create_nonce('se_pro_calendar')); ?>');
            }

            function attachCalendarHandlers() {
                var prev = document.querySelector('.se-pro-calendar__prev');
                var next = document.querySelector('.se-pro-calendar__next');
                if (prev) {
                    prev.addEventListener('click', function(e) {
                        e.preventDefault();
                        navigateMonth(this.dataset.date);
                    });
                }
                if (next) {
                    next.addEventListener('click', function(e) {
                        e.preventDefault();
                        navigateMonth(this.dataset.date);
                    });
                }
            }

            attachCalendarHandlers();
        })();
        </script>
        <?php
    }
}

add_action('wp_ajax_simple_events_pro_calendar_month', 'simple_events_pro_calendar_month_handler');
add_action('wp_ajax_nopriv_simple_events_pro_calendar_month', 'simple_events_pro_calendar_month_handler');

function simple_events_pro_calendar_month_handler() {
    check_ajax_referer('se_pro_calendar', 'nonce', false);

    $month = isset($_POST['month']) ? sanitize_text_field(wp_unslash($_POST['month'])) : current_time('Y-m-d');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $month)) {
        wp_die();
    }

    Simple_Events_Pro_Calendar::render_calendar_month($month);
    wp_die();
}
