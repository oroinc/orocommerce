define(function(require) {
    'use strict';

    var QuickAddImportValidationComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');

    QuickAddImportValidationComponent = BaseComponent.extend({
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
        constructor: function QuickAddImportValidationComponent() {
            QuickAddImportValidationComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            mediator.on('widget:contentLoad', this.onWidgetRender, this);
        },

        submitAction: function(widget) {
            var itemRows = $(this.options.itemsTableRows, widget.el);
            var result = [];
            var that = this;

            itemRows.each(function(index, element) {
                var $fields = $('td', element);

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
            var title = _.template(this.options.titleTemplate);
            var subtitle = '';
            var that = this;

            widgetManager.getWidgetInstance(this.options._wid, function(widget) {
                var dialogWidget = widget.getWidget();
                var instanceData = dialogWidget.get(0);
                var instance = $.data(instanceData, 'ui-dialog');

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
