define(function(require) {
    'use strict';

    var LineItemProductPricesView;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var ProductPricesEditableView = require('oropricing/js/app/views/product-prices-editable-view');

    LineItemProductPricesView = ProductPricesEditableView.extend({
        elements: _.extend({}, ProductPricesEditableView.prototype.elements, {
            currency: '[data-name="field__currency"]'
        }),

        modelElements: _.extend({}, ProductPricesEditableView.prototype.modelElements, {
            currency: 'currency'
        }),

        modelEvents: _.extend({}, ProductPricesEditableView.prototype.modelEvents, {
            'id updateTierPrices': ['change', 'updateTierPrices'],
            'currency changePricesCurrency': ['change', 'changePricesCurrency']
        }),

        options: _.extend({}, ProductPricesEditableView.prototype.options, {
            allowPriceEdit: true
        }),

        storedPrice: {},

        /**
         * @inheritDoc
         */
        deferredInitialize: function(options) {
            LineItemProductPricesView.__super__.deferredInitialize.apply(this, arguments);

            mediator.on('pricing:collect:line-items', this.collectLineItems, this);
            mediator.on('pricing:refresh:products-tier-prices', this.refreshTierPrices, this);

            mediator.trigger('pricing:get:products-tier-prices', _.bind(function(tierPrices) {
                this.setTierPrices(tierPrices, true);
            }, this));

            this.updateTierPrices();
            if (!this.options.allowPriceEdit) {
                this.getElement('priceValue').prop('disabled', true);
                var productId = this.model.get('id');
                if (!_.isUndefined(productId) && productId.length) {
                    this.storedPrice = {
                        productId: productId,
                        unit: this.model.get('unit'),
                        price: this.model.get('price'),
                        currency: this.model.get('currency')
                    };
                }
            }
        },

        updateTierPrices: function() {
            var productId = this.model.get('id');
            if (productId.length === 0) {
                this.setTierPrices({});
            } else {
                mediator.trigger(
                    'pricing:load:products-tier-prices',
                    [productId],
                    _.bind(this.refreshTierPrices, this)
                );
                mediator.trigger(
                    'pricing:refresh:products-tier-prices',
                    this.tierPrices
                );
            }
        },

        changePricesCurrency: function() {
            this.setTierPrices(this.tierPrices);
            var price = this.findPrice();

            if (!price &&
                this.storedPrice &&
                this.model.get('id') === this.storedPrice.productId &&
                this.model.get('unit') === this.storedPrice.unit &&
                this.model.get('currency') === this.storedPrice.currency
            ) {
                price = this.storedPrice;
            }
            this.setPriceValue(price ? price.price : null);
            this.getElement('priceValue').addClass('matched-price');
        },

        /**
         * @param {Object} tierPrices
         * @param {Boolean} silent
         */
        setTierPrices: function(tierPrices, silent) {
            this.tierPrices = tierPrices;
            var prices = {};

            var productId = this.model.get('id');
            if (!_.isUndefined(productId) && productId.length !== 0) {
                prices = tierPrices[productId] || {};
            }

            var currency = this.model.get('currency');
            if (currency) {
                prices = _.filter(prices, function(price) {
                    return price.currency === currency;
                });
            }

            this.model.set('prices', prices, {
                silent: silent || false
            });
        },

        /**
         * @param {Object} tierPrices
         * @param {Boolean} silent
         */
        refreshTierPrices: function(tierPrices, silent) {
            var productId = this.model.get('id');
            if (productId && !_.isUndefined(tierPrices) && _.isUndefined(tierPrices[productId])) {
                return;
            }
            this.setTierPrices(tierPrices, silent);
            this.setPrices();
            if (!this.options.allowPriceEdit) {
                this.filterValues();
            }
        },


        /**
         * @param {Array} items
         */
        collectLineItems: function(items) {
            var productId = this.model.get('id');

            if (!_.isUndefined(productId) && productId.length) {
                items.push({
                    product: productId,
                    unit: this.model.get('unit'),
                    quantity: this.model.get('quantity'),
                    currency: this.model.get('currency')
                });
            }
        },

        filterValues: function() {
            var prices = {};

            var productId = this.model.get('id');
            if (!_.isUndefined(productId) && productId.length !== 0) {
                prices = this.tierPrices[productId] || {};
            }
            var currencies = [];
            var units = [];

            _.each(prices, function(price) {
                currencies.push(price.currency);
                units.push(price.unit);
            });

            this.getElement('currency')
                .find('option')
                .show()
                .filter(function() {
                    return !$(this).prop('selected') && (-1 === $.inArray(this.value, currencies));
                })
                .hide();

            mediator.trigger(
                'product:unit:filter-values',
                productId,
                units
            );
        },

        getHintContent: function() {
            if (_.isEmpty(this.prices)) {
                return '';
            }

            return $(this.templates.pricesHintContent({
                model: this.model.attributes,
                prices: this.prices,
                matchedPrice: this.findPrice(),
                clickable: this.options.allowPriceEdit,
                formatter: NumberFormatter
            }));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);

            LineItemProductPricesView.__super__.dispose.call(this);
        }
    });

    return LineItemProductPricesView;
});
