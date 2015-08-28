define(function(require) {
    'use strict';

    var NotesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2border/js/app/components/notes-component
     * @extends oroui.app.components.base.Component
     * @class orob2border.app.components.NotesComponent
     */
    NotesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                edit: '.notes-widget-edit',
                preview: '.notes-widget-preview',
                addBtn: '.notes-widget-add-btn',
                removeBtn: '.notes-widget-remove-btn'
            },
            template: '#order-notes-widget'
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $notes: null,

        /**
         * @property {jQuery}
         */
        $edit: null,

        /**
         * @property {jQuery}
         */
        $preview: null,

        /**
         * @property {jQuery}
         */
        $addBtn: null,

        /**
         * @property {jQuery}
         */
        $removeBtn: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.$el = options._sourceElement;

            this.initUI();
        },

        initUI: function() {
            this.$notes = this.$el.find('textarea');
            this.$el.html($(this.options.template).text());

            this.$edit = this.$el.find(this.options.selectors.edit);
            this.$preview = this.$el.find(this.options.selectors.preview);
            this.$addBtn = this.$el.find(this.options.selectors.addBtn);
            this.$removeBtn = this.$el.find(this.options.selectors.removeBtn);

            this.$edit.prepend(this.$notes);

            this.$notes.change(_.bind(this.change, this));
            this.$notes.blur(_.bind(this.change, this));
            this.$preview.click(_.bind(this.addNotes, this));
            this.$addBtn.click(_.bind(this.addNotes, this))
                .mousedown(_.bind(this.addNotes, this));
            this.$removeBtn.click(_.bind(this.removeNotes, this));

            this.changed();
        },

        getVal: function() {
            return this.$notes.val();
        },

        change: function(e) {
            if (e.relatedTarget === this.$addBtn.get(0)) {
                this.addNotes(e);
            } else if (e.relatedTarget === this.$removeBtn.get(0)) {
                this.removeNotes(e);
            } else {
                this.changed();
            }
        },

        changed: function() {
            if (this.getVal().length === 0) {
                this.removeNotes();
            } else {
                this.showPreview();
            }
        },

        addNotes: function() {
            this.$notes.show().focus();
            this.$preview.hide();
            this.$removeBtn.show();
            this.$addBtn.hide();
        },

        removeNotes: function() {
            this.$notes.val('');
            this.$notes.hide();
            this.$preview.hide();
            this.$removeBtn.hide();
            this.$addBtn.show();
        },

        showPreview: function() {
            this.$preview.text(this.getVal());
            this.$notes.hide();
            this.$preview.show();
            this.$removeBtn.hide();
            this.$addBtn.hide();
        }
    });

    return NotesComponent;
});
