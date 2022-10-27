define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const ProductsPricesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            customer: null,
            currency: null,
            tierPrices: null,
            tierPricesRoute: '',
            requestKeys: {
                ACCOUNT: 'customer_id',
                CURRENCY: 'currency'
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function ProductsPricesComponent(options) {
            ProductsPricesComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.initPricesListeners();
            this.initFieldsListeners();

            mediator.trigger('pricing:refresh:products-tier-prices', this.options.tierPrices);
        },

        initPricesListeners: function() {
            this.listenTo(mediator, {
                'pricing:load:prices': this.reloadPrices,
                'pricing:get:products-tier-prices': this.getProductsTierPrices,
                'pricing:load:products-tier-prices': this.loadProductsTierPrices
            });
        },

        initFieldsListeners: function() {
            this.listenTo(mediator, {
                'update:currency': this.setCurrency,
                'customer-customer-user:change': this.setCustomer
            });
        },

        /*
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
         */
        loadProductsTierPrices: function(products, callback) {
            const context = {
                requestAttributes: {}
            };
            mediator.trigger('pricing:refresh:products-tier-prices:before', context);
            this.joinSubrequests(this.loadProductsTierPrices, products, callback, (products, callback) => {
                let params = {
                    product_ids: products
                };
                params[this.options.requestKeys.CURRENCY] = this.getCurrency();
                params[this.options.requestKeys.ACCOUNT] = this.getCustomer();
                params = _.extend({}, params, context.requestAttributes || {});

                $.get(routing.generate(this.options.tierPricesRoute, params), callback);
            });
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
                const data = storage.data;
                const callbacks = storage.callbacks;

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
            const items = [];
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

        getCustomer: function() {
            return this.options.customer;
        },

        setCustomer: function({customerId}) {
            if (customerId === this.options.customer) {
                return;
            }

            this.options.customer = customerId;
            this.reloadPrices();
        }
    });

    return ProductsPricesComponent;
});
