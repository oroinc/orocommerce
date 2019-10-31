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
         * @inheritDoc
         */
        constructor: function CurrencySwitcherComponent(options) {
            CurrencySwitcherComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('click', this.options.currencyElement, _.bind(this.onCurrencyChange, this));
        },

        onCurrencyChange: function(e) {
            e.preventDefault();
            const $el = $(e.target);

            const currency = $el.data('currency');
            if (currency !== this.options.selectedCurrency) {
                mediator.execute('showLoading');
                $.post(
                    routing.generate(this.options.currencySwitcherRoute),
                    {currency: currency},
                    function() {
                        mediator.execute('refreshPage');
                    }
                );
            }
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
