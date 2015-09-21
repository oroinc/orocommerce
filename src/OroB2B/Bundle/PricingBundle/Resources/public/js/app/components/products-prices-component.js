define(function(require) {
    'use strict';

    var ProductsPricesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ProductsPricesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            $priceList: null,
            $currency: null,
            tierPrices: null,
            tierPricesRoute: '',
            matchedPrices: {},
            matchedPricesRoute: ''
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            mediator.on('pricing:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.on('pricing:load:products-tier-prices', this.loadProductsTierPrices, this);

            mediator.on('pricing:get:line-items-matched-prices', this.getLineItemsMatchedPrices, this);
            mediator.on('pricing:load:line-items-matched-prices', this.loadLineItemsMatchedPrices, this);

            if (this.options.$priceList) {
                this.options.$priceList.change(_.bind(function() {
                    this.loadProductsTierPrices(this.getProductsId(), function(response) {
                        mediator.trigger('pricing:refresh:products-tier-prices', response);
                    });

                    this.loadLineItemsMatchedPrices(this.getItems(), function(response) {
                        mediator.trigger('pricing:refresh:line-items-matched-prices', response);
                    });
                }, this));
            }
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

            $.ajax({
                url: routing.generate(this.options.matchedPricesRoute, params),
                type: 'GET',
                success: function(response) {
                    callback(response);
                },
                error: function(response) {
                    callback();
                }
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
            if (_.isObject(this.options.$currency)) {
                return this.options.$currency.val();
            } else {
                return this.options.$currency;
            }
        },

        /**
         * @returns {String}
         * @private
         */
        _getPriceList: function() {
            return this.options.$priceList.length !== 0 ? this.options.$priceList.val() : '';
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('pricing:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.off('pricing:load:products-tier-prices', this.loadProductsTierPrices, this);

            mediator.off('pricing:get:line-items-matched-prices', this.getLineItemsMatchedPrices, this);
            mediator.off('pricing:load:line-items-matched-prices', this.loadLineItemsMatchedPrices, this);

            ProductsPricesComponent.__super__.dispose.call(this);
        }
    });

    return ProductsPricesComponent;
});
