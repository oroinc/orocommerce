import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const TextTypeBuilder = BaseTypeBuilder.extend({
    constructor: function TextTypeBuilder(options) {
        TextTypeBuilder.__super__.constructor.call(this, options);
    },

    initialize(options) {
        Object.assign(this, _.pick(options, 'editor', 'componentType'));
    },

    execute: function() {
        this.editor.BlockManager.get(this.componentType).set({
            label: __('oro.cms.wysiwyg.component.text.label'),
            content: {
                type: 'text',
                content: __('oro.cms.wysiwyg.component.text.content'),
                style: {
                    padding: '10px'
                },
                activeOnRender: 1
            }
        });
    }
});

export default TextTypeBuilder;
