import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const QuiteBasicTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'text',

    button: {
        label: __('oro.cms.wysiwyg.component.quote.label')
    },

    modelMixin: {
        defaults: {
            tagName: 'blockquote',
            classes: ['quote'],
            content: __('oro.cms.wysiwyg.component.quote.content')
        }
    },

    constructor: function QuiteBasicTypeBuilder(options) {
        QuiteBasicTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        if (el.nodeType === 1 && el.tagName.toLowerCase() === 'blockquote') {
            return {
                type: this.componentType
            };
        }
    }
});

export default QuiteBasicTypeBuilder;
