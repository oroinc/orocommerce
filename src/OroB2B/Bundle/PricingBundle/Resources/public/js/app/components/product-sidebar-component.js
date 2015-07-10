/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ProductSidebarComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        routing = require('routing'),
        messenger = require('oroui/js/messenger'),
        __ = require('orotranslation/js/translator');

    ProductSidebarComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            priceListSelector: '.priceListSelectorContainer',
            currenciesSelector: '.currenciesSelectorContainer',
            routeName: 'orob2b_pricing_price_list_currency_list',
            routingParams: {},
            currencyTemplate: '<input type="checkbox" value="<%- value %>"><%- text %>'
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
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});
            this.currenciesContainer = this.options._sourceElement.find(this.options.currenciesSelector);

            this.options._sourceElement
                .on('change', this.options.priceListSelector, _.bind(this.onPriceListChange, this));
        },

        onPriceListChange: function (e) {
            var value = e.target.value;

            var routeParams = $.extend({}, this.options.routingParams, {'id': value});
            $.ajax({
                url: routing.generate(this.options.routeName, routeParams),
                beforeSend: $.proxy(this._beforeSend, this),
                success: $.proxy(this._success, this),
                complete: $.proxy(this._complete, this),
                error: function (jqXHR) {
                    messenger.showErrorMessage(__(self.options.errorMessage), jqXHR.responseJSON);
                }
            });
        },

        /**
         * @private
         */
        _beforeSend: function () {
            this.loadingMaskView.show();
        },

        /**
         * @param {Object} data
         *
         * @private
         */
        _success: function (data) {
            var html = '',
                self = this,
                index = 0;
            _.each(data, function (value, key) {
                var template = _.template(self.options.currencyTemplate);

                html += template({
                    value: key,
                    text: value,
                    ftid: index,
                    uid: _.uniqueId('ocs')
                });

                index++;
            });

            this.currenciesContainer.html(html);
        },

        /**
         * @private
         */
        _complete: function () {
            this.loadingMaskView.hide();
        },


        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            ProductSidebarComponent.__super__.dispose.call(this);
        }
    });

    return ProductSidebarComponent;
});
