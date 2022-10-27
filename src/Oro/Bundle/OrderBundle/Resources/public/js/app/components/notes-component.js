define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export oroorder/js/app/components/notes-component
     * @extends oroui.app.components.base.Component
     * @class oroorder.app.components.NotesComponent
     */
    const NotesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                edit: '.notes-widget-edit',
                preview: '.notes-widget-preview',
                editBtn: '.notes-widget-edit-btn',
                addBtn: '.notes-widget-add-btn',
                removeBtn: '.notes-widget-remove-btn'
            },
            template: '#order-notes-widget'
        },

        /**
         * @property {Function}
         */
        template: null,

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
        $editBtn: null,

        /**
         * @property {jQuery}
         */
        $removeBtn: null,

        /**
         * @inheritdoc
         */
        constructor: function NotesComponent(options) {
            NotesComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.$el = options._sourceElement;
            this.template = _.template($(this.options.template).text());

            this.initUI();
        },

        initUI: function() {
            this.$notes = this.$el.find('textarea');
            this.$el.html(this.template());

            this.$edit = this.$el.find(this.options.selectors.edit);
            this.$preview = this.$el.find(this.options.selectors.preview);
            this.$addBtn = this.$el.find(this.options.selectors.addBtn);
            this.$editBtn = this.$el.find(this.options.selectors.editBtn);
            this.$removeBtn = this.$el.find(this.options.selectors.removeBtn);

            this.$edit.prepend(this.$notes);

            this.$notes.change(this.change.bind(this));
            this.$notes.blur(this.change.bind(this));
            this.$preview.click(this.addNotes.bind(this));
            this.$addBtn.click(this.addNotes.bind(this))
                .mousedown(this.addNotes.bind(this));
            this.$editBtn.click(this.addNotes.bind(this))
                .mousedown(this.addNotes.bind(this));
            this.$removeBtn.click(this.removeNotes.bind(this))
                .mousedown(this.removeNotes.bind(this));

            this.changed();
            this.$el.show();
        },

        hasVal: function() {
            return this.$notes.val().replace(/\s/g, '').length > 0;
        },

        change: function(e) {
            if (e.relatedTarget === this.$addBtn.get(0) || e.relatedTarget === this.$editBtn.get(0)) {
                this.addNotes(e);
            } else if (e.relatedTarget === this.$removeBtn.get(0)) {
                this.removeNotes(e);
            } else {
                this.changed();
            }
        },

        changed: function() {
            if (!this.hasVal()) {
                this.removeNotes();
            } else {
                this.showPreview();
            }
        },

        addNotes: function(e) {
            this.$notes.show().focus();
            this.$preview.hide();
            this.$removeBtn.show();
            this.$addBtn.hide();
            this.$editBtn.hide();
            if (e) {
                e.preventDefault();
            }
        },

        removeNotes: function(e) {
            this.$notes.val('');
            this.showPreview();
            if (e) {
                e.preventDefault();
            }
        },

        showPreview: function() {
            if (this.hasVal()) {
                this.$preview.text(this.$notes.val()).show();
                this.$addBtn.hide();
                this.$editBtn.show();
            } else {
                this.$notes.val('');
                this.$preview.text('').hide();
                this.$addBtn.show();
                this.$editBtn.hide();
            }
            this.$notes.hide();
            this.$removeBtn.hide();
        }
    });

    return NotesComponent;
});
