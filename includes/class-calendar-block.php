<?php
/**
 * Gutenberg Calendar block for Simple Events Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Events_Pro_Calendar_Block {

    /**
     * @var Simple_Events_Pro_Calendar_Shortcode
     */
    private $calendar_shortcode;

    /**
     * @param Simple_Events_Pro_Calendar_Shortcode $calendar_shortcode Calendar renderer.
     */
    public function __construct($calendar_shortcode) {
        $this->calendar_shortcode = $calendar_shortcode;
        add_action('init', array($this, 'register_block'));
    }

    /**
     * Register the dynamic calendar block.
     */
    public function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'simple-events-pro-calendar-block',
            plugin_dir_url(SIMPLE_EVENTS_PRO_FILE) . 'assets/js/calendar-block.js',
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'),
            SIMPLE_EVENTS_PRO_VERSION,
            true
        );

        register_block_type('simple-events-pro/event-calendar', array(
            'api_version'     => 2,
            'editor_script'   => 'simple-events-pro-calendar-block',
            'editor_style'    => 'simple-events-pro-calendar',
            'style'           => 'simple-events-pro-calendar',
            'attributes'      => array(
                'month'        => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'show_filters' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
            'render_callback' => array($this, 'render_calendar'),
        ));
    }

    /**
     * Render the calendar using the shared shortcode renderer.
     *
     * @param array $attributes Block attributes.
     * @return string HTML output.
     */
    public function render_calendar($attributes) {
        return $this->calendar_shortcode->render(array(
            'month'        => isset($attributes['month']) ? sanitize_text_field($attributes['month']) : '',
            'show_filters' => !empty($attributes['show_filters']) ? 'true' : 'false',
        ));
    }
}
