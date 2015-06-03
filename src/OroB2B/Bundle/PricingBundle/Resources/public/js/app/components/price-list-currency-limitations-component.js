/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var PriceListCurrencyLimitationComponent,
        _ = require('underscore'),
        routing = require('routing'),
        messenger = require('oroui/js/messenger'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        BaseComponent = require('oroui/js/app/components/base/component');

    PriceListCurrencyLimitationComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            priceListSelector: 'input[name$="[priceList]"]',
            currencySelector: 'select[name$="[price][currency]"]',
            container: '.oro-item-collection',
            currenciesRoute: 'orob2b_pricing_price_list_currency_list'
        },

        /**
         * @property {array}
         */
        currencies: {},

        /**
         * @property {array}
         */
        systemSupportedCurrencyOptions: {},

        /**
         * @property {Object}
         */
        $priceListSelect: null,

        /**
         * @property {Object}
         */
        $currencySelect: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.$elem = options._sourceElement;

            this.loadingMaskView = new LoadingMaskView({container: this.$elem});
            this.currencies = this.$elem.closest(options.container).data('currencies');
            this.$priceListSelect = this.$elem.find(options.priceListSelector);
            this.$currencySelect = this.$elem.find(options.currencySelector);
            this.$currencySelect.find('option').clone().each(
                _.bind(
                    function (idx, option) {
                        this.systemSupportedCurrencyOptions[option.value] = option;
                    },
                    this
                )
            );

            this.prepareCurrencySelect();
            this.$elem.on('change', this.$priceListSelect, _.bind(this.prepareCurrencySelect, this));
        },

        /**
         * Prepare currency list select for selected price list
         */
        prepareCurrencySelect: function () {
            var priceListId = this.$priceListSelect.val();

            if (!priceListId) {
                this.$currencySelect.attr('disabled', 'disabled');
                return;
            }

            var priceListCurrencies = this.currencies[priceListId];

            if (priceListCurrencies) {
                this.handleCurrencies(priceListCurrencies);
            } else {
                var self = this;
                $.ajax({
                    url: routing.generate(this.options.currenciesRoute, {'id': priceListId}),
                    type: 'GET',
                    beforeSend: function () {
                        self.loadingMaskView.show();
                    },
                    success: function (response) {
                        priceListCurrencies = response;
                        self.currencies[priceListId] = priceListCurrencies;
                        self.$elem.closest(self.options.container).data('currencies', {priceListId: self.currencies});
                        self.handleCurrencies(priceListCurrencies);
                    },
                    complete: function () {
                        self.loadingMaskView.hide();
                    },
                    error: function (xhr) {
                        Error.handle({}, xhr, {enforce: true});
                    }
                });
            }
        },

        /**
         * @param {array} priceListCurrencies
         */
        handleCurrencies: function (priceListCurrencies) {
            // Add empty key for empty value placeholder
            priceListCurrencies.unshift('');

            var newOptions = _.filter(
                this.systemSupportedCurrencyOptions,
                function (option, key) {
                    return _.indexOf(priceListCurrencies, key) !== -1;
                }
            );

            this.$currencySelect.html(newOptions);
            this.$currencySelect.removeAttr("disabled");
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.$elem.off();

            PriceListCurrencyLimitationComponent.__super__.dispose.call(this);
        }
    });

    return PriceListCurrencyLimitationComponent;
});
