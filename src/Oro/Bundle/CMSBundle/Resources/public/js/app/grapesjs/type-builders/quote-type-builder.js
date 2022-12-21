import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from './base-type-builder';

const QuiteBasicTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'text',

    button: {
        label: __('oro.cms.wysiwyg.component.quote.label'),
        category: 'Basic',
        order: 20,
        attributes: {
            'class': 'fa fa-quote-right'
        }
    },

    modelMixin: {
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

    constructor: function QuiteBasicTypeBuilder(options) {
        QuiteBasicTypeBuilder.__super__.constructor.call(this, options);
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
});

export default QuiteBasicTypeBuilder;
