define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const __ = require('orotranslation/js/translator');

    const ProductPriceDebugSidebarComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            websitesSelector: '.priceListSelectorContainer',
            customersSelector: '.priceListSelectorContainer',
            currenciesSelector: '.currenciesSelectorContainer',
            showTierPricesSelector: '.showTierPricesSelectorContainer',
            sidebarAlias: 'product-prices-debug-sidebar',
            currenciesRouteName: 'oro_pricing_price_product_debug_currency_list',
            routingParams: {},
            currencyTemplate: `<label for="<%- id %>" class="checkbox-label">
                <input type="checkbox" id="<%- id %>" value="<%- value %>"><%- text %>
            </label>`
        },

        /**
         * @property {Object}
         */
        currenciesState: {},

        /**
         * @property {jQuery.Element}
         */
        currenciesContainer: null,

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritdoc
         */
        constructor: function ProductPriceDebugSidebarComponent(options) {
            ProductPriceDebugSidebarComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});
            this.currenciesContainer = this.options._sourceElement.find(this.options.currenciesSelector);

            this.options._sourceElement
                .on('change', this.options.websitesSelector, this.onPriceListChange.bind(this))
                .on('change', this.options.customersSelector, this.onPriceListChange.bind(this))
                .on('change', this.options.currenciesSelector, this.onCurrenciesChange.bind(this))
                .on('change', this.options.showTierPricesSelector, this.onShowTierPricesChange.bind(this));
        },

        onPriceListChange: function() {
            const routeParams = $.extend({}, this.options.routingParams, {
                website: $(this.options.websitesSelector).val(),
                customer: $(this.options.customersSelector).val(),
            });
            this._saveCurrenciesState();

            $.ajax({
                url: routing.generate(this.options.currenciesRouteName, routeParams),
                beforeSend: this._beforeSend.bind(this),
                success: this._success.bind(this),
                complete: this._complete.bind(this),
                errorHandlerMessage: __(this.options.errorMessage)
            });
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
            let currencies = this._saveCurrenciesState();
            if (_.isEmpty(currencies)) {
                currencies = false;
            }

            const params = {
                website: $(this.options.websitesSelector).val(),
                customer: $(this.options.customersSelector).val(),
                priceCurrencies: currencies,
                showTierPrices: $(this.options.showTierPricesSelector).prop('checked')
            };

            mediator.trigger(
                'grid-sidebar:change:' + this.options.sidebarAlias,
                {widgetReload: Boolean(widgetReload), params: params}
            );
        },

        _saveCurrenciesState: function() {
            let currencies = [];
            _.each($(this.options.currenciesSelector + ' input'), function(input) {
                const checked = input.checked;
                const value = $(input).val();
                if (checked) {
                    currencies.push(value);
                }
                this.currenciesState[value] = checked;
            }, this);

            return currencies;
        },

        /**
         * @private
         */
        _beforeSend: function() {
            this.loadingMaskView.show();
        },

        /**
         * @param {Object} data
         *
         * @private
         */
        _success: function(data) {
            const html = [];
            let index = 0;
            const template = _.template(this.options.currencyTemplate);
            if (!this._hasActiveCurrencies(data)) {
                this.currenciesState = {};
            }

            _.each(data, function(value, key) {
                let checked = '';
                if (this.currenciesState.hasOwnProperty(key) && this.currenciesState[key]) {
                    checked = 'checked';
                }
                html[index] = template({
                    value: key,
                    text: key,
                    ftid: index,
                    uid: _.uniqueId('ocs'),
                    checked: checked
                });

                index++;
            }, this);

            this.currenciesContainer.html(html.join(''));

            this.triggerSidebarChanged(false);
        },

        _hasActiveCurrencies: function(data) {
            for (const key in this.currenciesState) {
                if (this.currenciesState.hasOwnProperty(key) && data.hasOwnProperty(key) && this.currenciesState[key]) {
                    return true;
                }
            }
            return false;
        },

        /**
         * @private
         */
        _complete: function() {
            this.loadingMaskView.hide();
            this.currenciesContainer.inputWidget('seekAndCreate');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            ProductPriceDebugSidebarComponent.__super__.dispose.call(this);
        }
    });

    return ProductPriceDebugSidebarComponent;
});
