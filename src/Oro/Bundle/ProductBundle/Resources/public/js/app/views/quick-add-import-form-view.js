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
        initialize(options) {
            _.extend(this, _.pick(options, 'droppableContainer', 'productsCollection'));

            // @deprecated no need for this handler without QuickAddImportWidget
            this.listenTo(mediator, {
                'quick-add-import-form:submit': this.onImportFormSubmit
            });
            QuickAddImportFormView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        delegateEvents() {
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
        undelegateEvents() {
            QuickAddImportFormView.__super__.undelegateEvents.call(this);
            if (this.$el && this.droppableContainer) {
                this.$el.closest(this.droppableContainer).off(this.eventNamespace());
            }
            return this;
        },

        onFileChange() {
            this.$el.submit();
        },

        onSubmit(event) {
            if (event.isDefaultPrevented() || !this.find('input:file')[0].value) {
                // in case form is invalid and submit has been already prevented
                return false;
            }

            event.preventDefault();

            // @deprecated all below in this method
            this.getWidget()
                .loadContentWithFormSubmit(this.$el);
        },

        onWidgetContentLoad() {
            this.find('input:file')[0].value = '';
        },

        onDragenter(event) {
            event.preventDefault();
            event.stopPropagation();
            this.highlight();
        },

        onDragover(event) {
            event.preventDefault();
            event.stopPropagation();
            this.highlight();
        },

        onDragleave(event) {
            event.preventDefault();
            event.stopPropagation();
            this.unhighlight();
        },

        onDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            this.unhighlight();
            const {dataTransfer} = event.originalEvent;

            if (dataTransfer && dataTransfer.files.length) {
                const _dataTransfer = new DataTransfer();
                // upload supported only for one file, so it is only first file is taken
                _dataTransfer.items.add(dataTransfer.files[0]);
                this.find('input:file')[0].files = _dataTransfer.files;

                this.$el.submit();
            }
        },

        /**
         * @deprecated method
         */
        getWidget() {
            const widget = new QuickAddImportWidget();

            widget.once('contentLoad', this.onWidgetContentLoad.bind(this));

            return widget;
        },

        highlight() {
            this.$el.addClass('highlight');
        },

        unhighlight() {
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
