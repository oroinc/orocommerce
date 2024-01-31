import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import routing from 'routing';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import currencyTemplate from 'tpl-loader!oropricing/templates/debug/currency-template.html';

const ProductPriceDebugSidebarView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'websitesSelector', 'customersSelector', 'currenciesSelector',
        'showTierPricesSelector', 'sidebarAlias', 'currenciesRouteName',
        'routingParams'
    ]),

    /**
     * @property {string}
     */
    websitesSelector: null,

    /**
     * @property {string}
     */
    customersSelector: null,

    /**
     * @property {string}
     */
    currenciesSelector: null,

    /**
     * @property {string}
     */
    showTierPricesSelector: null,

    /**
     * @property {string}
     */
    sidebarAlias: 'product-prices-debug-sidebar',

    /**
     * @property {string}
     */
    currenciesRouteName: 'oro_pricing_price_product_debug_currency_list',

    currencyTemplate,

    /**
     * @property {jQuery.Element}
     */
    currenciesContainer: null,

    /**
     * @property {LoadingMaskView}
     */
    loadingMaskView: null,

    events() {
        const events = {
            [`change ${this.customersSelector}`]: 'onPriceListChange',
            [`change ${this.showTierPricesSelector}`]: 'onShowTierPricesChange'
        };

        if (this.websitesSelector) {
            events[`change ${this.websitesSelector}`] = 'onPriceListChange';
        }

        if (this.currenciesSelector) {
            events[`change ${this.currenciesSelector}`] = 'onCurrenciesChange';
        }

        return events;
    },

    /**
     * @inheritdoc
     */
    constructor: function ProductPriceDebugSidebarView(...args) {
        this.currenciesState = {};
        this.routingParams = {};
        ProductPriceDebugSidebarView.__super__.constructor.apply(this, args);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        ProductPriceDebugSidebarView.__super__.initialize.call(this, options);

        this.currenciesContainer = this.$(this.currenciesSelector);
        this.subview('loadingMaskView', new LoadingMaskView({
            container: this.$el
        }));
    },

    onPriceListChange() {
        const routeParams = {
            ...this.routingParams,
            website: this.$(this.websitesSelector).val(),
            customer: this.$(this.customersSelector).val()
        };
        this._saveCurrenciesState();

        $.ajax({
            url: routing.generate(this.currenciesRouteName, routeParams),
            beforeSend: this._beforeSend.bind(this),
            success: this._success.bind(this),
            complete: this._complete.bind(this),
            errorHandlerMessage: __(this.errorMessage)
        });
    },

    onCurrenciesChange() {
        this.triggerSidebarChanged(true);
    },

    onShowTierPricesChange() {
        this.triggerSidebarChanged(false);
    },

    /**
     * @param {Boolean} widgetReload
     */
    triggerSidebarChanged(widgetReload) {
        let currencies = this._saveCurrenciesState();

        if (_.isEmpty(currencies)) {
            currencies = false;
        }

        const params = {
            customer: this.$(this.customersSelector).val(),
            priceCurrencies: currencies,
            showTierPrices: this.$(this.showTierPricesSelector).prop('checked')
        };

        if (this.websitesSelector) {
            params['website'] = this.$(this.websitesSelector).val();
        }

        mediator.trigger(
            'grid-sidebar:change:' + this.sidebarAlias,
            {widgetReload: Boolean(widgetReload), params: params}
        );
    },

    _saveCurrenciesState() {
        if (!this.currenciesSelector) {
            return [];
        }

        const currencies = [];

        _.each(this.$(`${this.currenciesSelector} input`), input => {
            const checked = input.checked;
            const value = input.value;

            if (checked) {
                currencies.push(value);
            }

            this.currenciesState[value] = checked;
        });

        return currencies;
    },

    /**
     * @private
     */
    _beforeSend() {
        this.subview('loadingMaskView').show();
    },

    /**
     * @param {Object} data
     *
     * @private
     */
    _success(data) {
        const html = [];
        let index = 0;

        if (!this._hasActiveCurrencies(data)) {
            this.currenciesState = {};
        }

        _.each(data, function(value, key) {
            let checked = '';

            if (this.currenciesState.hasOwnProperty(key) && this.currenciesState[key]) {
                checked = 'checked';
            }

            html[index] = this.currencyTemplate({
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

    _hasActiveCurrencies(data) {
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
    _complete() {
        this.subview('loadingMaskView').hide();
        this.currenciesContainer.inputWidget('seekAndCreate');
    }
});

export default ProductPriceDebugSidebarView;

