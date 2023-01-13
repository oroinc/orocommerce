import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const LinkBlockTypeBuilder = BaseType.extend({
    parentType: 'link',

    button: {
        category: 'Basic',
        label: __('oro.cms.wysiwyg.component.link_block.label'),
        attributes: {
            'class': 'fa fa-external-link'
        },
        defaultStyle: {
            'display': 'inline-block',
            'padding': '5px',
            'min-height': '50px',
            'min-width': '50px'
        },
        order: 40
    },

    modelProps: {
        defaults: {
            tagName: 'a',
            editable: false,
            droppable: true,
            classes: ['link-block'],
            traits: ['href', 'title', 'target'],
            components: []
        }
    },

    viewProps: {
        init() {
            this.$el.off('dblclick');
        }
    },

    constructor: function LinkBlockTypeBuilder(options) {
        LinkBlockTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'A' && el.classList.contains('link-block');
    }
}, {
    type: 'link-block',
    priority: 260
});

export default LinkBlockTypeBuilder;
