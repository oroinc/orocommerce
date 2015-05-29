/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var PriceListCurrencyLimitationComponent,
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component');

    PriceListCurrencyLimitationComponent = BaseComponent.extend({
        /**
         * @property {array}
         */
        currencies: {},

        /**
         * @property {Object}
         */
        $systemSupportedCurrencyOptions: null,

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
            this.$elem = options._sourceElement;

            this.currencies = this.$elem.closest('.oro-product-price-collection').data('currencies');
            this.$priceListSelect = this.$elem.find('input[name$="[priceList]"]');
            this.$currencySelect = this.$elem.find('select[name$="[price][currency]"]');
            this.systemSupportedCurrencyOptions = {};
            this.$currencySelect.find('option').clone().each(
                _.bind(
                    function (idx, option) {
                        this.systemSupportedCurrencyOptions[option.value] = option;
                    },
                    this
                )
            );

            this.prepareCurrencySelect();
            this.$priceListSelect.on('change', _.bind(this.prepareCurrencySelect, this));
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
        }
    });

    return PriceListCurrencyLimitationComponent;
});
