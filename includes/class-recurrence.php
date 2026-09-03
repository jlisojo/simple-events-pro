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

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post_' . Simple_Events_Helpers::POST_TYPE, array($this, 'save_meta_box'));
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

        update_post_meta($post_id, self::META_FREQUENCY, $frequency);
        update_post_meta($post_id, self::META_INTERVAL, $interval);
        update_post_meta($post_id, self::META_END_DATE, $end_date);
    }
}