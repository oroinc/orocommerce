import __ from 'orotranslation/js/translator';
import TextTypeBuilder from 'orocms/js/app/grapesjs/type-builders/text-type-builder';

const QuiteBasicTypeBuilder = TextTypeBuilder.extend({
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
                type: this.componentType,
                textComponent: true
            };
        }
    }
});

export default QuiteBasicTypeBuilder;
