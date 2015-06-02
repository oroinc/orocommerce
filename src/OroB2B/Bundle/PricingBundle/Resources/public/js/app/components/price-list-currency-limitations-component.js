/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var PriceListCurrencyLimitationComponent,
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component');

    PriceListCurrencyLimitationComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            priceListSelector: 'input[name$="[priceList]"]',
            currencySelector: 'select[name$="[price][currency]"]',
            container: '.oro-product-price-collection'
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

            var $elem = options._sourceElement;
            this.currencies = $elem.closest(options.container).data('currencies');
            this.$priceListSelect = $elem.find(options.priceListSelector);
            this.$currencySelect = $elem.find(options.currencySelector);
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
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            PriceListCurrencyLimitationComponent.__super__.dispose.call(this);
        }
    });

    return PriceListCurrencyLimitationComponent;
});
