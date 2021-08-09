define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const mediator = require('oroui/js/mediator');

    const FrontendProductSidebarComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            currenciesSelector: '',
            showTierPricesSelector: '',
            sidebarAlias: 'frontend-products-sidebar'
        },

        /**
         * @inheritdoc
         */
        constructor: function FrontendProductSidebarComponent(options) {
            FrontendProductSidebarComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('change', this.options.currenciesSelector, this.onCurrenciesChange.bind(this))
                .on('change', this.options.showTierPricesSelector, this.onShowTierPricesChange.bind(this));
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
            const currency = $(this.options.currenciesSelector).val();

            const params = {
                priceCurrencies: currency,
                showTierPrices: $(this.options.showTierPricesSelector).prop('checked'),
                saveState: true
            };

            mediator.trigger(
                'grid-sidebar:change:' + this.options.sidebarAlias,
                {widgetReload: Boolean(widgetReload), params: params}
            );
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
