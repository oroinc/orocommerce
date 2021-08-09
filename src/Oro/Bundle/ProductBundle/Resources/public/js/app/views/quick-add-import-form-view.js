define(function(require) {
    'use strict';

    const _ = require('underscore');
    const QuickAddImportWidget = require('oro/quick-add-import-widget');
    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');

    const QuickAddImportFormView = BaseView.extend({
        /**
         * @type {string}
         */
        droppableContainer: '#container',

        events: {
            'change input:file': 'onFileChange',
            'submit': 'onSubmit',
            'dragenter': 'onDragenter',
            'dragover': 'onDragover',
            'dragleave': 'onDragleave',
            'drop': 'onDrop'
        },

        /**
         * @inheritdoc
         */
        constructor: function QuickAddImportFormView(options) {
            QuickAddImportFormView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, 'droppableContainer', 'productsCollection'));

            this.listenTo(mediator, {
                'quick-add-import-form:submit': this.onImportFormSubmit
            });
            QuickAddImportFormView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        delegateEvents: function() {
            QuickAddImportFormView.__super__.delegateEvents.call(this);

            if (this.droppableContainer) {
                const events = _.reduce({
                    dragenter: 'onDragenter',
                    dragover: 'onDragover',
                    dragleave: 'onDragleave',
                    drop: 'onDrop'
                }, function(result, handler, eventName) {
                    result[eventName + this.eventNamespace()] = this[handler].bind(this);
                    return result;
                }, {}, this);

                this.$el.closest(this.droppableContainer).on(events);
            }

            return this;
        },

        /**
         * @inheritdoc
         */
        undelegateEvents: function() {
            QuickAddImportFormView.__super__.undelegateEvents.call(this);
            if (this.$el && this.droppableContainer) {
                this.$el.closest(this.droppableContainer).off(this.eventNamespace());
            }
            return this;
        },

        onFileChange: function() {
            this.$el.submit();
        },

        onSubmit: function(event) {
            if (event.isDefaultPrevented() || !this.find('input:file')[0].value) {
                // in case form is invalid and submit has been already prevented
                return false;
            }

            event.preventDefault();

            this.getWidget()
                .loadContentWithFormSubmit(this.$el);
        },

        onWidgetContentLoad: function() {
            this.find('input:file')[0].value = '';
        },

        onDragenter: function(event) {
            event.preventDefault();
            event.stopPropagation();
            this.highlight();
        },

        onDragover: function(event) {
            event.preventDefault();
            event.stopPropagation();
            this.highlight();
        },

        onDragleave: function(event) {
            event.preventDefault();
            event.stopPropagation();
            this.unhighlight();
        },

        onDrop: function(event) {
            event.preventDefault();
            event.stopPropagation();
            this.unhighlight();

            if (
                event.originalEvent.dataTransfer &&
                event.originalEvent.dataTransfer.files.length
            ) {
                // upload supported only for one file, so it is only first file is taken
                this.getWidget()
                    .loadContentWithFileUpload(event.originalEvent.dataTransfer.files[0], this.$el);
            }
        },

        getWidget: function() {
            const widget = new QuickAddImportWidget();

            widget.once('contentLoad', this.onWidgetContentLoad.bind(this));

            return widget;
        },

        highlight: function() {
            this.$el.addClass('highlight');
        },

        unhighlight: function() {
            this.$el.removeClass('highlight');
        },

        async onImportFormSubmit(items) {
            if (this.productsCollection) {
                await this.productsCollection.addQuickAddRows(items);
            }
        }
    });

    return QuickAddImportFormView;
});
