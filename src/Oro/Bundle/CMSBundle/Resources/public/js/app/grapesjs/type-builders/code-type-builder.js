import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const CodeTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'text',

    button: {
        label: __('oro.cms.wysiwyg.component.code.label'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-code'
        }
    },

    modelMixin: {
        defaults: {
            tagName: 'code',
            content: __('oro.cms.wysiwyg.component.code.placeholder')
        }
    },

    constructor: function CodeTypeBuilder(options) {
        CodeTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'CODE') {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default CodeTypeBuilder;
