define(function(require) {
    'use strict';

    var FrontendProductSidebarComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');

    FrontendProductSidebarComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            currenciesSelector: '',
            showTierPricesSelector: ''
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('change', this.options.currenciesSelector, _.bind(this.onCurrenciesChange, this))
                .on('change', this.options.showTierPricesSelector, _.bind(this.onShowTierPricesChange, this));
        },

        onCurrenciesChange: function() {
            this.triggerSidebarChanged(true);
        },

        onShowTierPricesChange: function() {
            this.triggerSidebarChanged(false);
        },

        /**
         * @param {Boolean} widgetReload
         */
        triggerSidebarChanged: function(widgetReload) {
            var currency = $(this.options.currenciesSelector).val();

            var params = {
                priceCurrency: currency,
                showTierPrices: $(this.options.showTierPricesSelector).prop('checked')
            };

            mediator.trigger('grid-sidebar:changed:products-sidebar', {widgetReload: Boolean(widgetReload), params: params});
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            FrontendProductSidebarComponent.__super__.dispose.call(this);
        }
    });

    return FrontendProductSidebarComponent;
});
