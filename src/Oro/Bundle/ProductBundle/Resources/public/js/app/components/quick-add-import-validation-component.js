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
         * @inheritdoc
         */
        constructor: function QuickAddImportValidationComponent(options) {
            QuickAddImportValidationComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.listenTo(mediator, {
                'widget:contentLoad': this.onWidgetRender
            });
        },

        submitAction: function(widget) {
            const itemRows = $(this.options.itemsTableRows, widget.el);
            const result = [];

            itemRows.each((index, element) => {
                result.push($(element).data('rowItem'));
            });
            mediator.trigger('quick-add-import-form:submit', result);
            widgetManager.getWidgetInstance(this.options._wid, this.closeWidget);
        },

        closeWidget: function(widget) {
            widget.remove();
        },

        onWidgetRender: function() {
            const title = _.template(this.options.titleTemplate);
            let subtitle = '';

            widgetManager.getWidgetInstance(this.options._wid, widget => {
                const dialogWidget = widget.getWidget();
                const instanceData = dialogWidget.get(0);
                const instance = $.data(instanceData, 'ui-dialog');

                widget
                    .off('adoptedFormSubmitClick')
                    .on('adoptedFormSubmitClick', this.submitAction.bind(this, widget));

                instance._title = title => {
                    if (instance.options.title) {
                        title.html(instance.options.title);
                    } else {
                        title.html('&#160;');
                    }
                };

                if (this.options.validItemsCount !== undefined) {
                    subtitle = __(
                        'oro.product.frontend.quick_add.import_validation.subtitle',
                        {count: this.options.validItemsCount},
                        this.options.validItemsCount
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
