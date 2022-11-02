import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const LinkBlockTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'link',

    button: {
        category: 'Basic',
        label: __('oro.cms.wysiwyg.component.link_block.label'),
        attributes: {
            'class': 'fa fa-link'
        },
        defaultStyle: {
            'display': 'inline-block',
            'padding': '5px',
            'min-height': '50px',
            'min-width': '50px'
        }
    },

    modelMixin: {
        defaults: {
            editable: false,
            droppable: true,
            classes: ['link-block'],
            traits: ['href', 'title', 'target'],
            components: []
        }
    },

    viewMixin: {
        init() {
            this.$el.off('dblclick');
        }
    },

    constructor: function LinkBlockTypeBuilder(options) {
        LinkBlockTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'A' && el.classList.contains('link-block')) {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default LinkBlockTypeBuilder;
