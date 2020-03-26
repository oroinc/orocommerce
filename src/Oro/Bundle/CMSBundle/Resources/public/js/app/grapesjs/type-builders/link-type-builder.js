import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const linkColor = window.getComputedStyle(document.documentElement).getPropertyValue('--secondary');

const LinkTypeBuilder = BaseTypeBuilder.extend({
    constructor: function LinkTypeBuilder(options) {
        LinkTypeBuilder.__super__.constructor.call(this, options);
    },

    initialize(options) {
        Object.assign(this, _.pick(options, 'editor', 'componentType'));
    },

    execute: function() {
        this.editor.BlockManager.get(this.componentType).set({
            label: __('oro.cms.wysiwyg.component.link.label'),
            content: {
                type: 'link',
                content: __('oro.cms.wysiwyg.component.link.content'),
                style: {
                    color: linkColor
                }
            }
        });
    }
});

export default LinkTypeBuilder;
