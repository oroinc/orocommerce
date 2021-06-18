import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const LinkButtonTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'link',

    button: {
        label: __('oro.cms.wysiwyg.component.link_button.label'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-hand-pointer-o'
        }
    },

    constructor: function LinkButtonTypeBuilder(options) {
        LinkButtonTypeBuilder.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        _.extend(this, _.pick(options, 'editor', 'componentType'));
    },

    execute() {
        this.editor.DomComponents.addType(this.componentType, {
            isComponent: el => {
                let result = null;

                if (el.tagName === 'A' && el.classList.contains('btn')) {
                    result = {
                        type: this.componentType
                    };
                }

                return result;
            },
            extend: this.parentType,
            model: {
                defaults: {
                    classes: ['btn', 'btn--info'],
                    tagName: 'a',
                    components: [{
                        type: 'textnode',
                        content: __('oro.cms.wysiwyg.component.link_button.content')
                    }]
                }
            }
        });

        this.createPanelButton();
    }
});

export default LinkButtonTypeBuilder;
