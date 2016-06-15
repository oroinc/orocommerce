define(function(require) {
    'use strict';

    var CurrencySwitcherComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');

    CurrencySwitcherComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            currencySwitcherRoute: 'orob2b_pricing_frontend_set_current_currency',
            currencyElement: '[data-currency]',
            selectedCurrency: null
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
            var $el = $(e.target);

            var currency = $el.data('currency');
            if (currency !== this.options.selectedCurrency) {
                mediator.execute('showLoading');
                $.post(
                    routing.generate(this.options.currencySwitcherRoute),
                    {'currency': currency},
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
