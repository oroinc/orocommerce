define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const widgetManager = require('oroui/js/widget-manager');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const mediator = require('oroui/js/mediator');

    const QuickAddImportValidationComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            _wid: '',
            validItemsCount: 0,
            itemsTableRows: 'table.quick_add_validation_items tbody tr'
        },

        /**
         * @inheritDoc
         */
        constructor: function QuickAddImportValidationComponent(options) {
            QuickAddImportValidationComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            mediator.on('widget:contentLoad', this.onWidgetRender, this);
        },

        submitAction: function(widget) {
            const itemRows = $(this.options.itemsTableRows, widget.el);
            const result = [];
            const that = this;

            itemRows.each(function(index, element) {
                const $fields = $('td', element);

                result.push({
                    sku: $fields.get(-1).textContent,
                    quantity: $fields.get(1).textContent,
                    unit: $fields.get(2).textContent
                });
            }).promise().done(function() {
                mediator.trigger('quick-add-import-form:submit', result);
                widgetManager.getWidgetInstance(that.options._wid, that.closeWidget);
            });
        },

        closeWidget: function(widget) {
            widget.dispose();
        },

        onWidgetRender: function() {
            const title = _.template(this.options.titleTemplate);
            let subtitle = '';
            const that = this;

            widgetManager.getWidgetInstance(this.options._wid, function(widget) {
                const dialogWidget = widget.getWidget();
                const instanceData = dialogWidget.get(0);
                const instance = $.data(instanceData, 'ui-dialog');

                widget
                    .off('adoptedFormSubmitClick')
                    .on('adoptedFormSubmitClick', _.bind(that.submitAction, that, widget));

                instance._title = function(title) {
                    if (this.options.title) {
                        title.html(this.options.title);
                    } else {
                        title.html('&#160;');
                    }
                };

                if (that.options.validItemsCount !== undefined) {
                    subtitle = __(
                        'oro.product.frontend.quick_add.import_validation.subtitle',
                        {count: that.options.validItemsCount},
                        that.options.validItemsCount
                    );
                }

                widget.setTitle(title({
                    title: __('oro.product.frontend.quick_add.import_validation.title'),
                    subtitle: subtitle
                }));
            });
        }
    });

    return QuickAddImportValidationComponent;
});
