define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');
    const widgetManager = require('oroui/js/widget-manager');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const ProductPricesUpdateWidget = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            widgetAlias: null,
            unitFormId: null,
            precisionFormId: null
        },

        /**
         * @inheritdoc
         */
        constructor: function ProductPricesUpdateWidget(options) {
            ProductPricesUpdateWidget.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            const form = this.options._sourceElement.closest('form');

            form.on('change', this.options.unitFormId, this.reloadPricesWidget.bind(this));
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off('change');
            ProductPricesUpdateWidget.__super__.dispose.call(this);
        },

        /**
         * @param {Event} e
         */
        reloadPricesWidget: function(e) {
            const unitId = $(this.options.unitFormId).val();
            const precision = $(this.options.precisionFormId).val();

            widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
                widget.setUrl(
                    routing.generate('oro_pricing_widget_prices_update', {
                        unit: unitId,
                        precision: precision
                    })
                );

                widget.render();
            });
        }
    });

    return ProductPricesUpdateWidget;
});
