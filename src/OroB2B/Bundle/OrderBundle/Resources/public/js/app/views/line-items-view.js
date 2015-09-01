define(function(require) {
    'use strict';

    var LineItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2border/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemsView
     */
    LineItemsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            tierPrices: null,
            matchedPrices: {},
            tierPricesRoute: 'orob2b_pricing_price_by_pricelist',
            matchedPricesRoute: 'orob2b_pricing_matching_price',
            currency: null
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $priceList: null,

        /**
         * @property {jQuery}
         */
        $currency: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$form = this.$el.closest('form');
            this.$priceList = this.$form.find(':input[data-ftid="' + this.$form.attr('name') + '_priceList"]');
            this.$currency = this.$form.find(':input[data-ftid="' + this.$form.attr('name') + '_currency"]');

            this.$el.find('.add-list-item').mousedown(function(e) {
                $(this).click();
            });

            mediator.on('order:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.on('order:load:products-tier-prices', this.loadProductsTierPrices, this);
            mediator.on('order:get:line-items-matched-prices', this.getLineItemsMatchedPrices, this);
            mediator.on('order:load:line-items-matched-prices', this.loadLineItemsMatchedPrices, this);

            this.$priceList.change(_.bind(function() {
                this.loadProductsTierPrices(this.getProductsId(), function(response) {
                    mediator.trigger('order:refresh:products-tier-prices', response);
                });
                this.loadLineItemsMatchedPrices(this.getItems(), function(response) {
                    mediator.trigger('order:refresh:line-items-matched-prices', response);
                });
            }, this));
        },

        /**
         * @param {Function} callback
         */
        getProductsTierPrices: function(callback) {
            callback(this.options.tierPrices);
        },

        /**
         * @param {Array} products
         * @param {Function} callback
         */
        loadProductsTierPrices: function(products, callback) {
            var params = {
                product_ids: products,
                currency: this._getCurrency()
            };

            var priceList = this._getPriceList();
            if (priceList.length !== 0) {
                params = _.extend(params, {price_list_id: priceList});
            }

            $.get(routing.generate(this.options.tierPricesRoute, params), function(response) {
                callback(response);
            });
        },

        /**
         * @returns {Array} products
         */
        getProductsId: function() {
            var products = this.$el.find('input[data-ftid$="_product"]');
            products = _.filter(products, function(product) {
                return product.value.length > 0;
            });
            products = _.map(products, function(product) {
                return product.value;
            });
            return products;
        },

        /**
         * @param {Function} callback
         */
        getLineItemsMatchedPrices: function(callback) {
            callback(this.options.matchedPrices);
        },

        /**
         * @param {Array} items
         * @param {Function} callback
         */
        loadLineItemsMatchedPrices: function(items, callback) {
            var params = {
                items: items,
                currency: this._getCurrency()
            };

            var priceList = this._getPriceList();
            if (priceList.length !== 0) {
                params = _.extend(params, {pricelist: priceList});
            }

            $.get(routing.generate(this.options.matchedPricesRoute, params), function(response) {
                callback(response);
            });
        },

        /**
         * @returns {Array} products
         */
        getItems: function() {
            var lineItems = this.$el.find('.order-line-item');
            var items = [];

            _.each(lineItems, function(lineItem) {
                var $lineItem = $(lineItem);
                var productId = $lineItem.find('input[data-ftid$="_product"]')[0].value;
                if (productId.length === 0) {
                    return;
                }

                var unitCode = $lineItem.find('select[data-ftid$="_productUnit"]')[0].value;
                var quantity = $lineItem.find('input[data-ftid$="_quantity"]')[0].value;

                items.push({'product': productId, 'unit': unitCode, 'qty': quantity});
            });

            return items;
        },

        /**
         * @returns {String}
         * @private
         */
        _getCurrency: function() {
            var currency = this.options.currency;
            if (this.$currency.length !== 0) {
                currency = this.$currency.val();
            }
            return currency;
        },

        /**
         * @returns {String}
         * @private
         */
        _getPriceList: function() {
            return this.$priceList.length !== 0 ? this.$priceList.val() : '';
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.off('order:load:products-tier-prices', this.loadProductsTierPrices, this);
            mediator.off('order:get:line-items-matched-prices', this.getLineItemsMatchedPrices, this);
            mediator.off('order:load:line-items-matched-prices', this.loadLineItemsMatchedPrices, this);

            LineItemsView.__super__.dispose.call(this);
        }
    });

    return LineItemsView;
});
