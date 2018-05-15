define(function(require) {
    'use strict';

    var ProductTotalPriceView;
    var BaseView = require('oroui/js/app/views/base/view');
    var PricesHelper = require('oropricing/js/app/prices-helper');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    var _ = require('underscore');

    ProductTotalPriceView = BaseView.extend(_.extend({}, ElementsHelper, {
        autoRender: false,

        elements: {
            totalQty: '[data-role="total-quantity"]',
            totalPrice: '[data-role="total-price"]'
        },

        prices: null,

        /**
         * @inheritDoc
         */
        constructor: function ProductTotalPriceView() {
            ProductTotalPriceView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ProductTotalPriceView.__super__.initialize.apply(this, arguments);
            this.initModel(options);
            this.setPrices();
            this.initializeElements(options);

            ShoppingListCollectionService.shoppingListCollection.done(_.bind(function(collection) {
                this.shoppingListCollection = collection;
                this.listenTo(collection, 'change', this.render);
            }, this));
        },

        initModel: function(options) {
            if (options.productModel) {
                this.model = options.productModel;
            }
        },

        render: function() {
            var totals = this._calcTotals();

            this.$el.toggleClass('hide', totals.quantity === 0);
            this.getElement('totalQty').text(totals.quantity);
            this.getElement('totalPrice').text(
                NumberFormatter.formatCurrency(totals.price)
            );
        },

        /**
         * Refactoring prices object model
         */
        setPrices: function() {
            this.prices = {};
            var prices = this.model.get('prices');

            _.each(prices, function(unitPrices, productId) {
                this.prices[productId] = PricesHelper.preparePrices(unitPrices);
            }, this);
        },

        _getCurrentShoppingList: function() {
            return this.shoppingListCollection.find({is_current: true});
        },

        _getCurrentLineItems: function() {
            var currentShoppingList = this._getCurrentShoppingList();
            if (!currentShoppingList) {
                return null;
            }
            return _.find(this.model.get('shopping_lists'), {id: currentShoppingList.get('id')}) || null;
        },

        _calcTotals: function() {
            var totals = {
                price: 0,
                quantity: 0
            };

            var lineItems = this._getCurrentLineItems();
            if (_.isNull(lineItems)) {
                return totals;
            }

            totals = _.reduce(lineItems.line_items, function(memo, lineItem) {
                var quantity = lineItem.quantity > 0 ? lineItem.quantity.toString() : '';

                memo.price += PricesHelper.calcTotalPrice(this.prices[lineItem.productId], lineItem.unit, quantity);
                memo.quantity += lineItem.quantity;

                return memo;
            }, totals, this);

            return totals;
        }
    }));

    return ProductTotalPriceView;
});
