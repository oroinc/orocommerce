import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const QuiteType = BaseType.extend({
    parentType: 'text',

    button: {
        label: __('oro.cms.wysiwyg.component.quote.label'),
        category: 'Basic',
        order: 20,
        attributes: {
            'class': 'fa fa-quote-right'
        }
    },

    modelProps: {
        defaults: {
            tagName: 'blockquote',
            classes: ['quote']
        },

        init() {
            const components = this.get('components');

            if (!components.length) {
                components.add([{
                    type: 'textnode',
                    content: __('oro.cms.wysiwyg.component.quote.content')
                }]);
            }
        }
    },

    constructor: function QuiteType(options) {
        QuiteType.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        let result = '';
        if (el.nodeType === Node.ELEMENT_NODE && el.tagName.toLowerCase() === 'blockquote') {
            result = {
                type: this.componentType,
                textComponent: true
            };
        }

        return result;
    }
}, {
    type: 'quote'
});

export default QuiteType;
