define(function(require) {
    'use strict';

    var BaseProductPricesView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var layout = require('oroui/js/layout');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var _ = require('underscore');
    var $ = require('jquery');

    BaseProductPricesView = BaseView.extend(_.extend({}, ElementsHelper, {
        autoRender: true,

        options: {
            unitLabel: 'oro.product.product_unit.%s.label.full',
            defaultQuantity: 1
        },

        elements: {
            price: '[data-name="price"]',
            unit: '[data-name="unit"]',
            priceValue: '[data-name="price-value"]',
            priceNotFound: '[data-name="price-not-found"]',
            pricesHint: '[data-name="prices-hint"]',
            pricesHintContent: '[data-name="prices-hint-content"]'
        },

        modelAttr: {
            prices: {},
            price: 0
        },

        modelEvents: {
            'prices setPrices': ['change', 'setPrices'],
            'quantity setPrice': ['change', 'onQuantityChange'],
            'unit setPrice': ['change', 'onUnitChange'],
            'price updateUI': ['change', 'updateUI']
        },

        prices: null,

        foundPrice: null,

        changeQuantity: false,

        initialize: function(options) {
            BaseProductPricesView.__super__.initialize.apply(this, arguments);

            $.extend(this, _.pick(options, ['changeQuantity']));

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);
        },

        dispose: function() {
            delete this.modelAttr;
            delete this.prices;
            delete this.foundPrice;
            this.disposeElements();
            BaseProductPricesView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.setPrices();
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        renderHint: function() {
            var $pricesHint = this.getElement('pricesHint');
            if (!$pricesHint.length) {
                return;
            }

            var content = this.getHintContent();
            $pricesHint.toggleClass('disabled', content.length === 0);

            if (!$pricesHint.data('popover')) {
                layout.initPopoverForElements($pricesHint, {
                    container: 'body'
                }, true);
            }

            $pricesHint.data('popover').updateContent(content);
        },

        getHintContent: function() {
            return this.getElement('pricesHintContent').html();
        },

        setPrices: function() {
            this.prices = {};
            this.foundPrice = {};

            _.each(this.model.get('prices'), function(price) {
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

            this.setPrice();
        },

        onQuantityChange: function(options) {
            if (options.manually) {
                this.changeQuantity = false;
            }
            this.setPrice();
        },

        onUnitChange: function(options) {
            this.setPrice(options.manually || false);
        },

        setPrice: function(changeQuantity) {
            this.setPriceValue(this.findPrice(changeQuantity));
        },

        setPriceValue: function(price) {
            this.model.set('price', price);
        },

        findPrice: function(changeQuantity) {
            var quantity = this.model.get('quantity');
            var unit = this.model.get('unit');
            var changeQuantity = changeQuantity && this.changeQuantity;

            var foundKey = unit + ' ' + quantity + ' ' + (changeQuantity ? 1 : 0);
            var price = null;

            if (!price) {
                if (changeQuantity) {
                    price = _.last(this.prices[unit]) || null;//sorted by quantity, get smallest
                } else {
                    price = _.find(this.prices[unit], function(price) {
                        return price.quantity <= quantity;
                    }) || null;
                }

                this.foundPrice[foundKey] = price;
            }

            if (changeQuantity) {
                var setQuantity = price ? price.quantity : this.options.defaultQuantity;
                if (quantity.toString() !== setQuantity.toString()) {
                    this.model.set('quantity', setQuantity);
                }
            }

            return price;
        },

        findPriceValue: function() {
            var price = this.findPrice();
            return price ? price.price : null;
        },

        updateUI: function() {
            var price = this.model.get('price');
            if (price === null) {
                this.getElement('price').addClass('hidden');
                this.getElement('priceNotFound').removeClass('hidden');
            } else {
                this.getElement('unit').html(_.__(this.options.unitLabel.replace('%s', price.unit)));

                price = NumberFormatter.formatCurrency(price.price, price.currency);
                this.getElement('priceValue').html(price);

                this.getElement('priceNotFound').addClass('hidden');
                this.getElement('price').removeClass('hidden');
            }
            this.renderHint();
        }
    }));

    return BaseProductPricesView;
});
