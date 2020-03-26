import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const QuiteBasicTypeBuilder = BaseTypeBuilder.extend({
    constructor: function QuiteBasicTypeBuilder(options) {
        QuiteBasicTypeBuilder.__super__.constructor.call(this, options);
    },

    initialize(options) {
        Object.assign(this, _.pick(options, 'editor', 'componentType'));
    },

    execute: function() {
        this.editor.BlockManager.get(this.componentType).set({
            label: __('oro.cms.wysiwyg.component.quote.label'),
            content: `<blockquote class="quote">${__('oro.cms.wysiwyg.component.quote.content')}</blockquote>`
        });
    }
});

export default QuiteBasicTypeBuilder;
