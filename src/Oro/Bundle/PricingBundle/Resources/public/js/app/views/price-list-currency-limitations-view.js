define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const BaseView = require('oroui/js/app/views/base/view');

    const PriceListCurrencyLimitationView = BaseView.extend({
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
         * @inheritdoc
         */
        events: function() {
            const events = {};
            events['change ' + this.options.priceListSelector] = this.prepareCurrencySelect.bind(this, true);
            return events;
        },

        /**
         * @inheritdoc
         */
        constructor: function PriceListCurrencyLimitationView(options) {
            PriceListCurrencyLimitationView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.currencies = this.$el.closest(options.container).data('currencies');

            this.prepareCurrencySelect(false);
        },

        /**
         * @inheritdoc
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
            const $collectionContainer = this.$el.closest(this.options.container);
            let currencyOptions = $collectionContainer.data('systemSupportedCurrencyOptions');

            if (!currencyOptions) {
                currencyOptions = {};
                $($collectionContainer.data('prototype'))
                    .find(this.options.currencySelector + ' option')
                    .each(function(i, option) {
                        const optionClone = option.cloneNode(true);
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
            const priceListId = this.$(this.options.priceListSelector).val();

            if (!priceListId) {
                const $currencySelect = this.$(this.options.currencySelector);
                $currencySelect.find('option[value=""]').show();
                $currencySelect.attr('disabled', 'disabled');
                $currencySelect.val('');
                $currencySelect.trigger('change');
                return;
            }

            if (_.has(this.currencies, priceListId)) {
                this.handleCurrencies(this.currencies[priceListId], selectFirst);
            } else {
                const loadingMaskView = this.getLoadingMaskView();
                $.ajax({
                    url: routing.generate(this.options.currenciesRoute, {id: priceListId}),
                    type: 'GET',
                    beforeSend: function() {
                        loadingMaskView.show();
                    },
                    success: function(response) {
                        const priceListCurrencies = _.keys(response);
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

            const optionElements = [];
            const systemSupportedCurrencyOptions = this.getSystemSupportedCurrencyOptions();
            const $currencySelect = this.$(this.options.currencySelector);
            const value = $currencySelect.val();
            $currencySelect.empty();
            _.each(priceListCurrencies, function(currency) {
                if (currency in systemSupportedCurrencyOptions) {
                    optionElements.push(systemSupportedCurrencyOptions[currency].cloneNode(true));
                }
            }, this);

            $currencySelect
                .append(optionElements)
                .removeAttr('disabled')
                .find('option[value=""]').hide();

            if (selectFirst && _.isEmpty(value)) {
                $currencySelect.val(priceListCurrencies[1]);
                $currencySelect.trigger('change');
            } else {
                $currencySelect.val(value);
            }
        },

        /**
         * Creates lazily loading mask subview
         *
         * @return {LoadingMaskView}
         */
        getLoadingMaskView: function() {
            let loadingMaskView = this.subview('loading-mask');
            if (!loadingMaskView) {
                loadingMaskView = new LoadingMaskView({container: this.$el});
                this.subview('loading-mask', loadingMaskView);
            }
            return loadingMaskView;
        }
    });

    return PriceListCurrencyLimitationView;
});
