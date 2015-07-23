/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ProductSidebarComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var __ = require('orotranslation/js/translator');

    ProductSidebarComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            priceListSelector: '.priceListSelectorContainer',
            currenciesSelector: '.currenciesSelectorContainer',
            showTierPricesSelector: '.showTierPricesSelectorContainer',
            sidebarAlias: 'products-sidebar',
            routeName: 'orob2b_pricing_price_list_currency_list',
            routingParams: {},
            currencyTemplate: '<input type="checkbox" id="<%- id %>" value="<%- value %>">' +
                '<label for="<%- id %>"><%- text %></label>'
        },

        /**
         * @property {jQuery.Element}
         */
        currenciesContainer: null,

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});
            this.currenciesContainer = this.options._sourceElement.find(this.options.currenciesSelector);

            this.options._sourceElement
                .on('change', this.options.priceListSelector, _.bind(this.onPriceListChange, this))
                .on('change', this.options.currenciesSelector, _.bind(this.onCurrenciesChange, this))
                .on('change', this.options.showTierPricesSelector, _.bind(this.onShowTierPricesChange, this));
        },

        onPriceListChange: function(e) {
            var value = e.target.value;
            var routeParams = $.extend({}, this.options.routingParams, {'id': value});

            $.ajax({
                url: routing.generate(this.options.routeName, routeParams),
                beforeSend: $.proxy(this._beforeSend, this),
                success: $.proxy(this._success, this),
                complete: $.proxy(this._complete, this),
                error: _.bind(
                    function(jqXHR) {
                        messenger.showErrorMessage(__(this.options.errorMessage), jqXHR.responseJSON);
                    },
                    this
                )
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
            var currencies = _.map($(this.options.currenciesSelector + ' input:checked'), function(input) {
                return $(input).val();
            });

            if (_.isEmpty(currencies)) {
                currencies = false;
            }

            var params = {
                priceListId: $(this.options.priceListSelector).val(),
                priceCurrencies: currencies,
                showTierPrices: $(this.options.showTierPricesSelector).prop('checked')
            };

            mediator.trigger(
                'grid-sidebar:changed:' + this.options.sidebarAlias,
                {widgetReload: Boolean(widgetReload), params: params}
            );
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
            var html = [];
            var index = 0;
            var template = _.template(this.options.currencyTemplate);

            _.each(data, function(value, key) {
                html[index] = template({
                    value: key,
                    text: value,
                    ftid: index,
                    uid: _.uniqueId('ocs')
                });

                index++;
            });

            this.currenciesContainer.html(html.join(''));

            this.triggerSidebarChanged(true);
        },

        /**
         * @private
         */
        _complete: function() {
            this.loadingMaskView.hide();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            ProductSidebarComponent.__super__.dispose.call(this);
        }
    });

    return ProductSidebarComponent;
});
