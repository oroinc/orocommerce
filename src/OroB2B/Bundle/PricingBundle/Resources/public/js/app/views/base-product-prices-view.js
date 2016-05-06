define(function(require) {
    'use strict';

    var BaseProductPricesView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var _ = require('underscore');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var tools = require('oroui/js/tools');

    BaseProductPricesView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            price: '[data-name="price"]',
            priceValue: '[data-name="price-value"]',
            priceNotFound: '[data-name="price-not-found"]',
            pricesHint: '[data-name="prices-hint"]',
            pricesHintContent: '[data-name="prices-hint-content"]'
        },

        prices: {},

        initialize: function(options) {
            BaseProductPricesView.__super__.initialize.apply(this, arguments);
            if (!this.model) {
                if (tools.debug) {
                    throw new Error('Model not defined!');
                }
                return;
            }
            this.initializeElements(options);

            this.setPrices(this.model.get('prices'));

            this.model.on('change:quantity', this.updatePrice, this);
            this.model.on('change:unit', this.updatePrice, this);

            this.render();
        },

        dispose: function() {
            this.disposeElements();
            BaseProductPricesView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.updatePrice();
            this.renderHint();
        },

        renderHint: function() {
            var $pricesHint = this.getElement('pricesHint');

            if ($pricesHint.length) {
                var $pricesHintContent = this.getElement('pricesHintContent');
                $pricesHint.data('content', $pricesHintContent.html());
                $pricesHint.popover({
                    animation: false,
                    html: true
                });
            }
        },

        setPrices: function(prices) {
            this.prices = {};
            _.each(prices, function(price) {
                if (!this.prices[price.unit]) {
                    this.prices[price.unit] = [];
                }
                this.prices[price.unit].push(price);
            }, this);

            //sort for optimize findPrice
            _.each(this.prices, function(unitPrices, unit) {
                unitPrices = _.sortBy(unitPrices, 'quantity');
                unitPrices.reverse();
                this.prices[unit] = unitPrices;
            }, this);
        },

        updatePrice: function() {
            var priceData = {
                quantity: this.model.get('quantity'),
                unit: this.model.get('unit')
            };

            this.renderPrice(this.findPrice(priceData));
        },

        findPrice: function(priceData) {
            if (!priceData || !_.isObject(priceData)) {
                return null;
            }
            return _.find(this.prices[priceData.unit], function(price) {
                return price.quantity <= priceData.quantity;
            }) || null;
        },

        renderPrice: function(price) {
            if (price === null) {
                this.getElement('price').hide();
                this.getElement('priceNotFound').show();
            } else {
                price = NumberFormatter.formatCurrency(price.price, price.currency);
                this.getElement('priceValue').html(price);

                this.getElement('priceNotFound').hide();
                this.getElement('price').show();
            }
        }
    }));

    return BaseProductPricesView;
});
