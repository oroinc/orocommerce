/** @lends TitleEditorView */
define(function(require) {
    'use strict';

    /**
     * Title cell content editor.
     *
     * @augments TextEditorView
     * @exports TitleEditorView
     */
    var TitleEditorView;
    var TextEditorView = require('oroform/js/app/views/editor/text-editor-view');

    TitleEditorView = TextEditorView.extend(/** @exports TitleEditorView.prototype */{
        template: require('tpl!../../../../templates/editor/title-editor.html'),
        className: 'form-input form-input_edit-mode cart__order-title_rename-mode',

        /**
         * Handles focusout event
         *
         * @param {jQuery.Event} e
         */
        onFocusout: function(e) {
            if (!this.$el.has(e.relatedTarget).length) {
                this.trigger('cancelAction');
            }
        }
    });

    return TitleEditorView;
});
