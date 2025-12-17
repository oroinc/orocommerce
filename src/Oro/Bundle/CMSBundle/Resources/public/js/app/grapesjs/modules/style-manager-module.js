import __ from 'orotranslation/js/translator';

const unitsSize = ['px', '%', 'em', 'rem', 'vh', 'vw'];
const unitsSizePerc = ['%', 'px'];
const unitsAngle = ['deg', 'rad', 'grad'];

/**
 *Style Manager property config
 */
export default [{
    id: 'general',
    buildProps: ['float', 'display', 'position', 'top', 'right', 'left', 'bottom'],
    properties: [{
        property: 'float',
        name: __('oro.cms.wysiwyg.style_manager.properties.float.name'),
        type: 'radio',
        defaults: 'none',
        list: [{
            value: 'none',
            title: __('oro.cms.wysiwyg.style_manager.properties.float.list.none'),
            className: 'fa fa-times'
        }, {
            value: 'left',
            title: __('oro.cms.wysiwyg.style_manager.properties.float.list.left'),
            className: 'fa fa-align-left'
        }, {
            value: 'right',
            title: __('oro.cms.wysiwyg.style_manager.properties.float.list.right'),
            className: 'fa fa-align-right'
        }]
    }, {
        property: 'display',
        name: __('oro.cms.wysiwyg.style_manager.properties.display.name')
    }, {
        property: 'top',
        name: __('oro.cms.wysiwyg.style_manager.properties.top.name')
    }, {
        property: 'right',
        name: __('oro.cms.wysiwyg.style_manager.properties.right.name')
    }, {
        property: 'bottom',
        name: __('oro.cms.wysiwyg.style_manager.properties.bottom.name')
    }, {
        name: __('oro.cms.wysiwyg.style_manager.properties.left.name'),
        property: 'left'
    }, {
        property: 'position',
        name: __('oro.cms.wysiwyg.style_manager.properties.position.name'),
        type: 'select'
    }]
}, {
    id: 'dimension',
    open: false,
    buildProps: ['width', 'height', 'max-width', 'min-height', 'margin', 'padding'],
    properties: [{
        property: 'width',
        name: __('oro.cms.wysiwyg.style_manager.properties.width.name')
    }, {
        property: 'height',
        name: __('oro.cms.wysiwyg.style_manager.properties.height.name')
    }, {
        property: 'max-width',
        name: __('oro.cms.wysiwyg.style_manager.properties.max_width.name')
    }, {
        property: 'min-height',
        name: __('oro.cms.wysiwyg.style_manager.properties.min_height.name')
    }, {
        property: 'margin',
        name: __('oro.cms.wysiwyg.style_manager.properties.margin.name'),
        properties: [{
            property: 'margin-top',
            name: __('oro.cms.wysiwyg.style_manager.properties.margin_top.name')
        }, {
            property: 'margin-right',
            name: __('oro.cms.wysiwyg.style_manager.properties.margin_right.name')
        }, {
            property: 'margin-bottom',
            name: __('oro.cms.wysiwyg.style_manager.properties.margin_bottom.name')
        }, {
            property: 'margin-left',
            name: __('oro.cms.wysiwyg.style_manager.properties.margin_left.name')
        }]
    }, {
        property: 'margin-inline-start',
        name: __('oro.cms.wysiwyg.style_manager.properties.margin_inline_start.name'),
        type: 'number',
        units: ['px', '%', 'em', 'rem', 'vh', 'vw']
    }, {
        property: 'margin-inline-end',
        name: __('oro.cms.wysiwyg.style_manager.properties.margin_inline_end.name'),
        type: 'number',
        units: ['px', '%', 'em', 'rem', 'vh', 'vw']
    }, {
        property: 'padding',
        name: __('oro.cms.wysiwyg.style_manager.properties.padding.name'),
        properties: [{
            property: 'padding-top',
            name: __('oro.cms.wysiwyg.style_manager.properties.padding_top.name')
        }, {
            property: 'padding-right',
            name: __('oro.cms.wysiwyg.style_manager.properties.padding_right.name')
        }, {
            property: 'padding-bottom',
            name: __('oro.cms.wysiwyg.style_manager.properties.padding_bottom.name')
        }, {
            property: 'padding-left',
            name: __('oro.cms.wysiwyg.style_manager.properties.padding_left.name')
        }]
    }, {
        property: 'padding-inline-start',
        name: __('oro.cms.wysiwyg.style_manager.properties.padding_inline_start.name'),
        type: 'number',
        units: ['px', '%', 'em', 'rem', 'vh', 'vw']
    }, {
        property: 'padding-inline-end',
        name: __('oro.cms.wysiwyg.style_manager.properties.padding_inline_end.name'),
        type: 'number',
        units: ['px', '%', 'em', 'rem', 'vh', 'vw']
    }]
}, {
    id: 'typography',
    open: false,
    buildProps: [
        'font-family', 'font-size', 'font-weight', 'letter-spacing', 'color',
        'line-height', 'text-align', 'text-decoration', 'text-shadow'
    ],
    properties: [{
        property: 'font-family',
        name: __('oro.cms.wysiwyg.style_manager.properties.font_family.name')
    }, {
        property: 'font-size',
        name: __('oro.cms.wysiwyg.style_manager.properties.font_size.name')
    }, {
        property: 'font-weight',
        name: __('oro.cms.wysiwyg.style_manager.properties.font_weight.name')
    }, {
        property: 'letter-spacing',
        name: __('oro.cms.wysiwyg.style_manager.properties.letter_spacing.name')
    }, {
        property: 'color',
        name: __('oro.cms.wysiwyg.style_manager.properties.color.name')
    }, {
        property: 'line-height',
        name: __('oro.cms.wysiwyg.style_manager.properties.line_height.name')
    }, {
        property: 'text-align',
        name: __('oro.cms.wysiwyg.style_manager.properties.text_align.name'),
        type: 'radio',
        defaults: 'left',
        list: [{
            value: 'left',
            title: __('oro.cms.wysiwyg.style_manager.properties.text_align_left.name'),
            className: 'fa fa-align-left'
        }, {
            value: 'center',
            title: __('oro.cms.wysiwyg.style_manager.properties.text_align_center.name'),
            className: 'fa fa-align-center'
        }, {
            value: 'right',
            title: __('oro.cms.wysiwyg.style_manager.properties.text_align_right.name'),
            className: 'fa fa-align-right'
        }, {
            value: 'justify',
            title: __('oro.cms.wysiwyg.style_manager.properties.text_align_justify.name'),
            className: 'fa fa-align-justify'
        }]
    }, {
        property: 'text-decoration',
        name: __('oro.cms.wysiwyg.style_manager.properties.text_decoration.name'),
        type: 'radio',
        defaults: 'none',
        list: [{
            value: 'none',
            title: __('oro.cms.wysiwyg.style_manager.properties.text_decoration_none.name'),
            className: 'fa fa-times'
        }, {
            value: 'underline',
            title: __('oro.cms.wysiwyg.style_manager.properties.text_decoration_underline.name'),
            className: 'fa fa-underline'
        }, {
            value: 'line-through',
            title: __('oro.cms.wysiwyg.style_manager.properties.text_decoration_line_through.name'),
            className: 'fa fa-strikethrough'
        }]
    }, {
        property: 'text-shadow',
        name: __('oro.cms.wysiwyg.style_manager.properties.text_shadow.name'),
        properties: [{
            property: 'text-shadow-h',
            name: __('oro.cms.wysiwyg.style_manager.properties.text_shadow_h.name')
        }, {
            property: 'text-shadow-v',
            name: __('oro.cms.wysiwyg.style_manager.properties.text_shadow_v.name')
        }, {
            property: 'text-shadow-blur',
            name: __('oro.cms.wysiwyg.style_manager.properties.text_shadow_blur.name')
        }, {
            property: 'text-shadow-color',
            name: __('oro.cms.wysiwyg.style_manager.properties.text_shadow_color.name')
        }]
    }]
}, {
    id: 'decorations',
    open: false,
    buildProps:
        ['opacity', 'mix-blend-mode', 'background-color', 'border-radius', 'border', 'box-shadow', 'background'],
    properties: [{
        property: 'opacity',
        name: __('oro.cms.wysiwyg.style_manager.properties.opacity.name'),
        type: 'slider',
        defaults: 1,
        step: 0.01,
        max: 1,
        min: 0
    }, {
        property: 'mix-blend-mode',
        name: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.name'),
        type: 'select',
        options: [{
            id: 'normal',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.normal')
        }, {
            id: 'multiply',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.multiply')
        }, {
            id: 'screen',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.screen')
        }, {
            id: 'overlay',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.overlay')
        }, {
            id: 'darken',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.darken')
        }, {
            id: 'lighten',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.lighten')
        }, {
            id: 'color-dodge',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.color_dodge')
        }, {
            id: 'color-burn',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.color_burn')
        }, {
            id: 'hard-light',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.hard_light')
        }, {
            id: 'soft-light',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.soft_light')
        }, {
            id: 'difference',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.difference')
        }, {
            id: 'exclusion',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.exclusion')
        }, {
            id: 'hue',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.hue')
        }, {
            id: 'saturation',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.saturation')
        }, {
            id: 'color',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.color')
        }, {
            id: 'luminosity',
            label: __('oro.cms.wysiwyg.style_manager.properties.mix_blend_mode.options.luminosity')
        }]
    }, {
        property: 'background-color',
        name: __('oro.cms.wysiwyg.style_manager.properties.background_color.name')
    }, {
        property: 'border',
        name: __('oro.cms.wysiwyg.style_manager.properties.border.name'),
        properties: [{
            property: 'border-width',
            name: __('oro.cms.wysiwyg.style_manager.properties.border_width.name')
        }, {
            property: 'border-style',
            name: __('oro.cms.wysiwyg.style_manager.properties.border_style.name')
        }, {
            property: 'border-color',
            name: __('oro.cms.wysiwyg.style_manager.properties.border_color.name')
        }]
    }, {
        property: 'border-radius',
        name: __('oro.cms.wysiwyg.style_manager.properties.border_radius.name'),
        properties: [{
            property: 'border-top-left-radius',
            name: __('oro.cms.wysiwyg.style_manager.properties.border_radius_top.name')
        }, {
            property: 'border-top-right-radius',
            name: __('oro.cms.wysiwyg.style_manager.properties.border_radius_right.name')
        }, {
            property: 'border-bottom-left-radius',
            name: __('oro.cms.wysiwyg.style_manager.properties.border_radius_bottom.name')
        }, {
            property: 'border-bottom-right-radius',
            name: __('oro.cms.wysiwyg.style_manager.properties.border_radius_left.name')
        }]
    }, {
        property: 'box-shadow',
        name: __('oro.cms.wysiwyg.style_manager.properties.box_shadow.name'),
        properties: [{
            property: 'box-shadow-h',
            name: __('oro.cms.wysiwyg.style_manager.properties.box_shadow_h.name')
        }, {
            property: 'box-shadow-v',
            name: __('oro.cms.wysiwyg.style_manager.properties.box_shadow_v.name')
        }, {
            property: 'box-shadow-blur',
            name: __('oro.cms.wysiwyg.style_manager.properties.box_shadow_blur.name')
        }, {
            property: 'box-shadow-spread',
            name: __('oro.cms.wysiwyg.style_manager.properties.box_shadow_spread.name')
        }, {
            property: 'box-shadow-color',
            name: __('oro.cms.wysiwyg.style_manager.properties.box_shadow_color.name')
        }, {
            property: 'box-shadow-type',
            name: __('oro.cms.wysiwyg.style_manager.properties.box_shadow_type.name')
        }]
    }, {
        property: 'background',
        name: __('oro.cms.wysiwyg.style_manager.properties.background.name'),
        properties: [{
            property: 'background-image',
            name: __('oro.cms.wysiwyg.style_manager.properties.background_image.name')
        }, {
            property: 'background-repeat',
            name: __('oro.cms.wysiwyg.style_manager.properties.background_repeat.name')
        }, {
            property: 'background-position',
            name: __('oro.cms.wysiwyg.style_manager.properties.background_position.name')
        }, {
            property: 'background-attachment',
            name: __('oro.cms.wysiwyg.style_manager.properties.background_attachment.name')
        }, {
            property: 'background-size',
            name: __('oro.cms.wysiwyg.style_manager.properties.background_size.name')
        }]
    }]
}, {
    id: 'extra',
    open: false,
    buildProps: ['transition', 'transform-origin', 'transform'],
    properties: [{
        property: 'transition',
        name: __('oro.cms.wysiwyg.style_manager.properties.transition.name'),
        properties: [{
            property: 'transition-property',
            name: __('oro.cms.wysiwyg.style_manager.properties.transition_property.name')
        }, {
            property: 'transition-duration',
            name: __('oro.cms.wysiwyg.style_manager.properties.transition_duration.name')
        }, {
            property: 'transition-timing-function',
            name: __('oro.cms.wysiwyg.style_manager.properties.transition_timing_function.name')
        }]
    }, {
        property: 'transform-origin',
        name: __('oro.cms.wysiwyg.style_manager.properties.transform_origin.name'),
        type: 'composite',
        properties: [
            {
                'type': 'number',
                'property': 'x-offset',
                'default': '50%',
                'label': __('oro.cms.wysiwyg.style_manager.properties.transform_origin_x_offset.name'),
                'units': unitsSizePerc
            },
            {
                'type': 'number',
                'property': 'y-offset',
                'default': '50%',
                'label': __('oro.cms.wysiwyg.style_manager.properties.transform_origin_y_offset.name'),
                'units': unitsSizePerc
            }
        ]
    }, {
        property: 'transform',
        name: __('oro.cms.wysiwyg.style_manager.properties.transform.name'),
        properties: [
            {
                'type': 'select',
                'property': 'name',
                'default': 'translateX',
                'label': __('oro.cms.wysiwyg.style_manager.properties.transform_type.name'),
                'options': [
                    {
                        id: 'translateX',
                        label: __('oro.cms.wysiwyg.style_manager.properties.transform_translate_x.name'),
                        propValue: {
                            units: unitsSize,
                            step: 1
                        }
                    },
                    {
                        id: 'translateY',
                        label: __('oro.cms.wysiwyg.style_manager.properties.transform_translate_y.name'),
                        propValue: {
                            units: unitsSize,
                            step: 1
                        }
                    },
                    {
                        id: 'rotate',
                        label: __('oro.cms.wysiwyg.style_manager.properties.transform_rotate.name'),
                        propValue: {
                            units: unitsAngle,
                            step: 1
                        }
                    },
                    {
                        id: 'scaleX',
                        label: __('oro.cms.wysiwyg.style_manager.properties.transform_scale_x.name'),
                        propValue: {
                            units: [''],
                            step: 0.1
                        }
                    },
                    {
                        id: 'scaleY',
                        label: __('oro.cms.wysiwyg.style_manager.properties.transform_scale_y.name'),
                        propValue: {
                            units: [''],
                            step: 0.1
                        }
                    },
                    {
                        id: 'skewX',
                        label: __('oro.cms.wysiwyg.style_manager.properties.transform_skew_x.name'),
                        propValue: {
                            units: unitsAngle,
                            step: 1
                        }
                    },
                    {
                        id: 'skewY',
                        label: __('oro.cms.wysiwyg.style_manager.properties.transform_skew_y.name'),
                        propValue: {
                            units: unitsAngle,
                            step: 1
                        }
                    }
                ]
            },
            {
                property: 'value',
                label: __('oro.cms.wysiwyg.style_manager.properties.transform_value.name')
            }
        ]
    }]
}, {
    id: 'flex',
    open: false,
    properties: [{
        property: 'display',
        name: __('oro.cms.wysiwyg.style_manager.flex_container'),
        type: 'select',
        defaults: 'block',
        list: [{
            value: 'block',
            name: __('oro.cms.wysiwyg.style_manager.disable')
        }, {
            value: 'flex',
            name: __('oro.cms.wysiwyg.style_manager.enable')
        }]
    }, {
        property: 'label-parent-flex',
        name: __('oro.cms.wysiwyg.style_manager.properties.label_parent_flex.name'),
        type: 'integer'
    }, {
        property: 'flex-direction',
        name: __('oro.cms.wysiwyg.style_manager.properties.flex_direction.name'),
        type: 'radio',
        defaults: 'row',
        list: [{
            value: 'row',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_direction_row.name'),
            className: 'gjs-icon-flex-dir-row'
        }, {
            value: 'row-reverse',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_direction_reverse.name'),
            className: 'gjs-icon-flex-dir-row-rev'
        }, {
            value: 'column',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_direction_column.name'),
            className: 'gjs-icon-flex-dir-col'
        }, {
            value: 'column-reverse',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_direction_column_reverse.name'),
            className: 'gjs-icon-flex-dir-col-rev'
        }]
    }, {
        property: 'justify-content',
        name: __('oro.cms.wysiwyg.style_manager.properties.justify_content.name'),
        type: 'radio',
        defaults: 'flex-start',
        list: [{
            value: 'flex-start',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_start.name'),
            className: 'gjs-icon-flex-just-start'
        }, {
            value: 'flex-end',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_end.name'),
            className: 'gjs-icon-flex-just-end'
        }, {
            value: 'space-between',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_space_between.name'),
            className: 'gjs-icon-flex-just-sp-bet'
        }, {
            value: 'space-around',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_space_around.name'),
            className: 'gjs-icon-flex-just-sp-ar'
        }, {
            value: 'center',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_center.name'),
            className: 'gjs-icon-flex-just-sp-cent'
        }]
    }, {
        property: 'align-items',
        name: __('oro.cms.wysiwyg.style_manager.properties.flex_align_items.name'),
        type: 'radio',
        defaults: 'center',
        list: [{
            value: 'flex-start',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_start.name'),
            className: 'gjs-icon-flex-al-start'
        }, {
            value: 'flex-end',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_end.name'),
            className: 'gjs-icon-flex-al-end'
        }, {
            value: 'stretch',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_stretch.name'),
            className: 'gjs-icon-flex-al-str'
        }, {
            value: 'center',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_center.name'),
            className: 'gjs-icon-flex-al-center'
        }]
    }, {
        property: 'label-parent-flex',
        name: __('oro.cms.wysiwyg.style_manager.properties.label_children_flex.name'),
        type: 'integer'
    }, {
        property: 'order',
        name: __('oro.cms.wysiwyg.style_manager.properties.flex_order.name'),
        type: 'integer',
        defaults: 0,
        min: 0
    }, {
        property: 'flex',
        name: __('oro.cms.wysiwyg.style_manager.properties.flex.name'),
        type: 'composite',
        properties: [{
            property: 'flex-grow',
            name: __('oro.cms.wysiwyg.style_manager.properties.flex_grow.name'),
            type: 'integer',
            defaults: 0,
            min: 0
        }, {
            property: 'flex-shrink',
            name: __('oro.cms.wysiwyg.style_manager.properties.flex_shrink.name'),
            type: 'integer',
            defaults: 0,
            min: 0
        }, {
            property: 'flex-basis',
            name: __('oro.cms.wysiwyg.style_manager.properties.flex_basis.name'),
            type: 'integer',
            unit: '',
            defaults: 'auto',
            toRequire: 1,
            units: ['px', '%', 'vw', 'vh'],
            fixedValues: ['initial', 'inherit', 'auto'],
            requiresParent: {
                display: ['flex']
            },
            min: 0
        }]
    }, {
        property: 'align-self',
        name: __('oro.cms.wysiwyg.style_manager.properties.flex_align_self.name'),
        type: 'radio',
        defaults: 'auto',
        list: [{
            value: 'auto',
            name: 'auto',
            title: __('oro.cms.wysiwyg.style_manager.auto')
        }, {
            value: 'flex-start',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_start.name'),
            className: 'gjs-icon-flex-al-start'
        }, {
            value: 'flex-end',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_end.name'),
            className: 'gjs-icon-flex-al-end'
        }, {
            value: 'stretch',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_stretch.name'),
            className: 'gjs-icon-flex-al-str'
        }, {
            value: 'center',
            title: __('oro.cms.wysiwyg.style_manager.properties.flex_center.name'),
            className: 'gjs-icon-flex-al-center'
        }]
    }]
}];

