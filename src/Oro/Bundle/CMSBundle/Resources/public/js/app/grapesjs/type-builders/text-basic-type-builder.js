import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const TextBasicTypeBuilder = BaseTypeBuilder.extend({
    constructor: function TextBasicTypeBuilder(options) {
        TextBasicTypeBuilder.__super__.constructor.call(this, options);
    },

    initialize(options) {
        Object.assign(this, _.pick(options, 'editor', 'componentType'));
    },

    execute: function() {
        this.editor.BlockManager.get(this.componentType).set({
            label: __('oro.cms.wysiwyg.component.text_basic.label'),
            content: `<section data-gjs-type="text">
                          <h1>${__('oro.cms.wysiwyg.component.text_basic.heading')}</h1>
                          <p>${__('oro.cms.wysiwyg.component.text_basic.paragraph')}</p>
                    </section>`
        });
    }
});

export default TextBasicTypeBuilder;
