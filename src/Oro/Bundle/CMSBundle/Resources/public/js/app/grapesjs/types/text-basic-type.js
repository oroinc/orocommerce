import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const TextBasicType = BaseType.extend({
    parentType: 'text',

    button: {
        label: __('oro.cms.wysiwyg.component.text_basic.label'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-align-left'
        }
    },

    modelProps: {
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
}, {
    type: 'text-basic',
    priority: 400
});

export default TextBasicType;
