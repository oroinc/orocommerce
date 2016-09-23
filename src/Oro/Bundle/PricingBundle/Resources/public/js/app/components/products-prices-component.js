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
            account: null,
            currency: null,
            tierPrices: null,
            tierPricesRoute: '',
            requestKeys: {
                ACCOUNT: 'account_id',
                CURRENCY: 'currency'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.initPricesListeners();
            this.initFieldsListeners();
        },

        initPricesListeners: function() {
            mediator.on('pricing:load:prices', this.reloadPrices, this);
            mediator.on('pricing:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.on('pricing:load:products-tier-prices', this.loadProductsTierPrices, this);
        },

        initFieldsListeners: function() {
            mediator.on('update:currency', this.setCurrency, this);
            mediator.on('update:account', this.setAccount, this);
        },

        /**
         * @param {Function} callback
         */
        getProductsTierPrices: function(callback) {
            callback(this.options.tierPrices);
        },

        reloadPrices: function() {
            this.loadProductsTierPrices(this.getProductsId(), function(response) {
                mediator.trigger('pricing:refresh:products-tier-prices', response);
            });
        },

        /**
         * @param {Array} products
         * @param {Function} callback
         * @param {Object} context
         */
        loadProductsTierPrices: function(products, callback) {
            var context =  {
                requestAttributes: {}
            };
            mediator.trigger('pricing:refresh:products-tier-prices:before', context);
            this.joinSubrequests(this.loadProductsTierPrices, products, callback, _.bind(function(products, callback) {
                var params = {
                    product_ids: products
                };
                params[this.options.requestKeys.CURRENCY] = this.getCurrency();
                params[this.options.requestKeys.ACCOUNT] = this.getAccount();
                params = _.extend({}, params, context.requestAttributes || {});

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
         * @returns {Array} line items
         */
        getLineItems: function() {
            var items = [];
            mediator.trigger('pricing:collect:line-items', items);
            return items;
        },

        getCurrency: function() {
            return this.options.currency;
        },

        setCurrency: function(val) {
            this.options.currency = val;
            this.reloadPrices();
        },

        getAccount: function() {
            return this.options.account;
        },

        setAccount: function(val) {
            this.options.account = val;
            this.reloadPrices();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);

            ProductsPricesComponent.__super__.dispose.call(this);
        }
    });

    return ProductsPricesComponent;
});
