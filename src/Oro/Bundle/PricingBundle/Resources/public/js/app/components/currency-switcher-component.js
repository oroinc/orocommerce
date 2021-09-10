define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const $ = require('jquery');
    const routing = require('routing');

    const CurrencySwitcherComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            currencySwitcherRoute: 'oro_pricing_frontend_set_current_currency',
            currencyElement: '[data-currency]',
            selectedCurrency: null
        },

        /**
         * @inheritdoc
         */
        constructor: function CurrencySwitcherComponent(options) {
            CurrencySwitcherComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('click', this.options.currencyElement, this.onCurrencyChange.bind(this));
        },

        onCurrencyChange: function(e) {
            e.preventDefault();
            const newCurrency = $(e.target).data('currency');
            const {selectedCurrency: initialCurrency} = this.options;

            mediator.execute('showLoading');
            this.syncActiveCurrency(newCurrency)
                // try to refresh page if currency is successfully changed
                // and return promise of page refresh action result
                .then(() => mediator.execute('refreshPage'))
                .fail(() => {
                    // rollback selected currency if refresh was canceled
                    this.syncActiveCurrency(initialCurrency)
                        .done(() => mediator.execute('hideLoading'));
                });
        },

        syncActiveCurrency(currency) {
            const url = routing.generate(this.options.currencySwitcherRoute);
            return $.post(url, {currency});
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            CurrencySwitcherComponent.__super__.dispose.call(this);
        }
    });

    return CurrencySwitcherComponent;
});
