import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const TextBasicTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'text',

    button: {
        label: __('oro.cms.wysiwyg.component.text_basic.label'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-align-left'
        }
    },

    modelMixin: {
        defaults: {
            name: __('oro.cms.wysiwyg.component.text_basic.label'),
            tagName: 'section'
        },

        init() {
            const component = this.get('components');

            if (!component.length) {
                component.add([{
                    type: 'text',
                    tagName: 'h1',
                    components: [{
                        type: 'textnode',
                        content: __('oro.cms.wysiwyg.component.text_basic.heading')
                    }]
                }, {
                    type: 'text',
                    tagName: 'p',
                    components: [{
                        type: 'textnode',
                        content: __('oro.cms.wysiwyg.component.text_basic.paragraph')
                    }]
                }]);
            }
        }
    },

    constructor: function TextBasicTypeBuilder(options) {
        TextBasicTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent() {
        return false;
    }
});

export default TextBasicTypeBuilder;
