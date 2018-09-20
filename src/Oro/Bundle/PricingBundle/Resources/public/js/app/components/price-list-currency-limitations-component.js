/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var PriceListCurrencyLimitationComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    PriceListCurrencyLimitationComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            priceListSelector: 'input[name$="[priceList]"]',
            currencySelector: 'select[name$="[price][currency]"]',
            container: '.oro-item-collection',
            currenciesRoute: 'oro_pricing_price_list_currency_list'
        },

        /**
         * @property {array}
         */
        currencies: {},

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
        constructor: function PriceListCurrencyLimitationComponent(options) {
            PriceListCurrencyLimitationComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$elem = options._sourceElement;

            this.loadingMaskView = new LoadingMaskView({container: this.$elem});
            this.currencies = this.$elem.closest(options.container).data('currencies');
            this.$priceListSelect = this.$elem.find(options.priceListSelector);
            this.$currencySelect = this.$elem.find(options.currencySelector);

            this.prepareCurrencySelect(false);
            this.$elem.one(
                'change',
                function() {
                    this.$elem.removeAttr('data-validation-ignore');
                }.bind(this)
            );
            this.$elem.on(
                'change',
                options.priceListSelector,
                _.bind(
                    function() {
                        this.prepareCurrencySelect(true);
                    },
                    this
                )
            );
        },

        /**
         * Fetches full list of currency options from the prototype of collection item
         * Preserves fetched collection in the collection container for reuse by other collection items
         *
         * @return {Object.<string, HTMLOptionElement>}
         */
        getSystemSupportedCurrencyOptions: function() {
            var $collectionContainer = this.$elem.closest(this.options.container);
            var currencyOptions = $collectionContainer.data('systemSupportedCurrencyOptions');

            if (!currencyOptions) {
                currencyOptions = {};
                $($collectionContainer.data('prototype'))
                    .find(this.options.currencySelector + ' option')
                    .each(function(i, option) {
                        var optionClone = option.cloneNode(true);
                        optionClone.removeAttribute('selected');
                        currencyOptions[optionClone.value] = optionClone;
                    });
                $collectionContainer.data('systemSupportedCurrencyOptions', currencyOptions);
            }

            return currencyOptions;
        },

        /**
         * Prepare currency list select for selected price list
         *
         *  @param {Boolean} selectFirst
         */
        prepareCurrencySelect: function(selectFirst) {
            var priceListId = this.$priceListSelect.val();
            var self = this;

            if (!priceListId) {
                this.$currencySelect.find('option[value=""]').show();
                this.$currencySelect.attr('disabled', 'disabled');
                this.$currencySelect.val('');
                this.$currencySelect.trigger('change');
                return;
            }

            if (_.has(this.currencies, priceListId)) {
                this.handleCurrencies(this.currencies[priceListId], selectFirst);
            } else {
                $.ajax({
                    url: routing.generate(this.options.currenciesRoute, {'id': priceListId}),
                    type: 'GET',
                    beforeSend: function() {
                        self.loadingMaskView.show();
                    },
                    success: function(response) {
                        var priceListCurrencies = _.keys(response);
                        self.currencies[priceListId] = priceListCurrencies;
                        self.$elem.closest(self.options.container).data('currencies', self.currencies);
                        self.handleCurrencies(priceListCurrencies, selectFirst);
                    },
                    complete: function() {
                        self.loadingMaskView.hide();
                    }
                });
            }
        },

        /**
         * @param {array} priceListCurrencies
         * @param {Boolean} selectFirst
         */
        handleCurrencies: function(priceListCurrencies, selectFirst) {
            // Add empty key for empty value placeholder
            if (priceListCurrencies.indexOf('') === -1) {
                priceListCurrencies.unshift('');
            }

            var systemSupportedCurrencyOptions = this.getSystemSupportedCurrencyOptions();
            var value = this.$currencySelect.val();
            this.$currencySelect.empty();
            _.each(priceListCurrencies, function(currency) {
                if (currency in systemSupportedCurrencyOptions) {
                    var newOption = systemSupportedCurrencyOptions[currency].cloneNode(true);
                    if (!_.isEmpty(value) && newOption.value === value) {
                        newOption.selected = true;
                    }
                    this.$currencySelect.append(newOption);
                }
            }, this);

            this.$currencySelect.find('option[value=""]').hide();
            this.$currencySelect.removeAttr('disabled');

            if (selectFirst && _.isEmpty(value)) {
                this.$currencySelect.val(priceListCurrencies[1]);
                this.$currencySelect.trigger('change');
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$elem.off();

            PriceListCurrencyLimitationComponent.__super__.dispose.call(this);
        }
    });

    return PriceListCurrencyLimitationComponent;
});
