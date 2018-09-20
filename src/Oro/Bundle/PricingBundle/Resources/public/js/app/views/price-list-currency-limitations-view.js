define(function(require) {
    'use strict';

    var PriceListCurrencyLimitationView;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseView = require('oroui/js/app/views/base/view');

    PriceListCurrencyLimitationView = BaseView.extend({
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
         * @inheritDoc
         */
        events: function() {
            var events = {};
            events['change ' + this.options.priceListSelector] = this.prepareCurrencySelect.bind(this, true);
            return events;
        },

        /**
         * @inheritDoc
         */
        constructor: function PriceListCurrencyLimitationView(options) {
            PriceListCurrencyLimitationView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.currencies = this.$el.closest(options.container).data('currencies');

            this.prepareCurrencySelect(false);
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function(events) {
            PriceListCurrencyLimitationView.__super__.delegateEvents.call(this, events);

            this.$el.one(
                'change' + this.eventNamespace(),
                function() {
                    this.$el.removeAttr('data-validation-ignore');
                }.bind(this)
            );

            return this;
        },

        /**
         * Fetches full list of currency options from the prototype of collection item
         * Preserves fetched collection in the collection container for reuse by other collection items
         *
         * @return {Object.<string, HTMLOptionElement>}
         */
        getSystemSupportedCurrencyOptions: function() {
            var $collectionContainer = this.$el.closest(this.options.container);
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
            var priceListId = this.$(this.options.priceListSelector).val();

            if (!priceListId) {
                var $currencySelect = this.$(this.options.currencySelector);
                $currencySelect.find('option[value=""]').show();
                $currencySelect.attr('disabled', 'disabled');
                $currencySelect.val('');
                $currencySelect.trigger('change');
                return;
            }

            if (_.has(this.currencies, priceListId)) {
                this.handleCurrencies(this.currencies[priceListId], selectFirst);
            } else {
                var loadingMaskView = this.getLoadingMaskView();
                $.ajax({
                    url: routing.generate(this.options.currenciesRoute, {id: priceListId}),
                    type: 'GET',
                    beforeSend: function() {
                        loadingMaskView.show();
                    },
                    success: function(response) {
                        var priceListCurrencies = _.keys(response);
                        this.currencies[priceListId] = priceListCurrencies;
                        this.$el.closest(this.options.container).data('currencies', this.currencies);
                        this.handleCurrencies(priceListCurrencies, selectFirst);
                    }.bind(this),
                    complete: function() {
                        loadingMaskView.hide();
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
            var $currencySelect = this.$(this.options.currencySelector);
            var value = $currencySelect.val();
            $currencySelect.empty();
            _.each(priceListCurrencies, function(currency) {
                if (currency in systemSupportedCurrencyOptions) {
                    var newOption = systemSupportedCurrencyOptions[currency].cloneNode(true);
                    if (!_.isEmpty(value) && newOption.value === value) {
                        newOption.selected = true;
                    }
                    $currencySelect.append(newOption);
                }
            }, this);

            $currencySelect.find('option[value=""]').hide();
            $currencySelect.removeAttr('disabled');

            if (selectFirst && _.isEmpty(value)) {
                $currencySelect.val(priceListCurrencies[1]);
                $currencySelect.trigger('change');
            }
        },

        /**
         * Creates lazily loading mask subview
         *
         * @return {LoadingMaskView}
         */
        getLoadingMaskView: function() {
            var loadingMaskView = this.subview('loading-mask');
            if (!loadingMaskView) {
                loadingMaskView = new LoadingMaskView({container: this.$el});
                this.subview('loading-mask', loadingMaskView);
            }
            return loadingMaskView;
        }
    });

    return PriceListCurrencyLimitationView;
});
