(function (blocks, element, blockEditor, components, serverSideRender, i18n) {
    var __ = i18n.__;
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var ServerSideRender = serverSideRender;

    blocks.registerBlockType('simple-events-pro/event-calendar', {
        title: __('Event Calendar', 'simple-events-pro'),
        description: __('Display a navigable monthly event calendar.', 'simple-events-pro'),
        icon: 'calendar-alt',
        category: 'widgets',
        keywords: [
            __('events', 'simple-events-pro'),
            __('calendar', 'simple-events-pro'),
            __('recurring', 'simple-events-pro')
        ],
        attributes: {
            month: {
                type: 'string',
                default: ''
            },
            show_filters: {
                type: 'boolean',
                default: true
            }
        },
        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, {
                        title: __('Calendar Settings', 'simple-events-pro'),
                        initialOpen: true
                    },
                    el(TextControl, {
                        label: __('Initial Month', 'simple-events-pro'),
                        help: __('Optional date in YYYY-MM-DD format. Leave blank for the current month.', 'simple-events-pro'),
                        value: attributes.month,
                        placeholder: '2026-09-01',
                        onChange: function (value) {
                            setAttributes({ month: value });
                        }
                    }),
                    el(ToggleControl, {
                        label: __('Show Category Filter', 'simple-events-pro'),
                        checked: attributes.show_filters,
                        onChange: function (value) {
                            setAttributes({ show_filters: value });
                        }
                    }))
                ),
                el('div', { key: 'preview', className: 'simple-events-pro-calendar-block-preview' },
                    el(ServerSideRender, {
                        block: 'simple-events-pro/event-calendar',
                        attributes: attributes
                    })
                )
            ];
        },
        save: function () {
            return null;
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components,
    window.wp.serverSideRender || window.wp.components.ServerSideRender,
    window.wp.i18n
);
