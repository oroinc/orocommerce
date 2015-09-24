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
            this.$el = this.options._sourceElement;

            mediator.on('pricing:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.on('pricing:load:products-tier-prices', this.loadProductsTierPrices, this);

            mediator.on('pricing:get:line-items-matched-prices', this.getLineItemsMatchedPrices, this);
            mediator.on('pricing:load:line-items-matched-prices', this.loadLineItemsMatchedPrices, this);

            if (this.options.$priceList) {
                this.options.$priceList.change(_.bind(function() {
                    this.loadProductsTierPrices(this.getProductsId(), function(response) {
                        mediator.trigger('pricing:refresh:products-tier-prices', response);
                    });

                    this.loadLineItemsMatchedPrices(this.getLineItems(), function(response) {
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
            this.joinSubrequests(this.loadProductsTierPrices, products, callback, _.bind(function(products, callback) {
                var params = {
                    product_ids: products,
                    currency: this._getCurrency()
                };

                var priceList = this._getPriceList();
                if (priceList.length !== 0) {
                    params = _.extend(params, {price_list_id: priceList});
                }

                $.get(routing.generate(this.options.tierPricesRoute, params), callback);
            }, this));
        },

        joinSubrequests: function(storage, data, callback, request) {
            storage.timeoutId = storage.timeoutId || null;
            storage.data = storage.data || [];
            storage.callbacks = storage.callbacks || [];

            storage.data = _.union(storage.data, data);
            storage.callbacks.push(callback);

            if (storage.timeoutId) {
                clearTimeout(storage.timeoutId);
            }

            storage.timeoutId = setTimeout(function() {
                var data = storage.data;
                var callbacks = storage.callbacks;

                storage.timeoutId = null;
                storage.data = [];
                storage.callbacks = [];

                request(data, function(response) {
                    _.each(callbacks, function(callback) {
                        callback(response);
                    });
                });
            }, 50);
        },

        /**
         * @returns {Array} products ID
         */
        getProductsId: function() {
            return _.map(this.getLineItems(), function(lineItem) {
                return lineItem.product;
            });
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
            this.joinSubrequests(this.loadLineItemsMatchedPrices, items, callback, _.bind(function(items, callback) {
                var params = {
                    items: items
                };

                var priceList = this._getPriceList();
                if (priceList.length !== 0) {
                    params = _.extend(params, {pricelist: priceList});
                }

                $.ajax({
                    url: routing.generate(this.options.matchedPricesRoute, params),
                    type: 'GET',
                    success: callback,
                    error: function(response) {
                        callback();
                    }
                });
            }, this));
        },

        /**
         * @returns {Array} line items
         */
        getLineItems: function() {
            var items = [];
            mediator.trigger('pricing:collect:line-items', items);
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
            return this.options.$priceList && this.options.$priceList.length !== 0 ? this.options.$priceList.val() : '';
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
