define(function(require) {
    'use strict';

    var BaseProductPricesView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var _ = require('underscore');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var tools = require('oroui/js/tools');

    BaseProductPricesView = BaseView.extend(_.extend({}, ElementsHelper, {
        options: {
            elements: {
                price: '[data-role="price"]',
                priceValue: '[data-role="price-value"]',
                priceNotFound: '[data-role="price-not-found"]',
                pricesHint: '[data-role="prices-hint"]',
                pricesHintContent: '[data-role="prices-hint-content"]'
            }
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

            this.listenTo(this.model, 'change:quantity', _.bind(this.updatePrice, this));
            this.listenTo(this.model, 'change:unit', _.bind(this.updatePrice, this));

            this.render();
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
            var self = this;

            this.prices = {};
            _.each(prices, function(price) {
                if (!self.prices[price.unit]) {
                    self.prices[price.unit] = [];
                }
                self.prices[price.unit].push(price);
            });

            //sort for optimize findPrice
            _.each(this.prices, function(unitPrices, unit) {
                unitPrices = _.sortBy(unitPrices, 'quantity');
                unitPrices.reverse();
                self.prices[unit] = unitPrices;
            });
        },

        updatePrice: function() {
            //todo: check if this.validate()
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
        },

        validate: function() {
            var validator = this.getElement('quantity').closest('form').validate();
            return !validator || validator.form();
        }
    }));

    return BaseProductPricesView;
});
