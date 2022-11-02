define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const PricesHelper = require('oropricing/js/app/prices-helper');
    const NumberFormatter = require('orolocale/js/formatter/number');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    const _ = require('underscore');

    const ProductTotalPriceView = BaseView.extend(_.extend({}, ElementsHelper, {
        autoRender: false,

        elements: {
            totalQty: '[data-role="total-quantity"]',
            totalPrice: '[data-role="total-price"]'
        },

        prices: null,

        /**
         * @inheritdoc
         */
        constructor: function ProductTotalPriceView(options) {
            ProductTotalPriceView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ProductTotalPriceView.__super__.initialize.call(this, options);
            this.initModel(options);
            this.setPrices();
            this.initializeElements(options);

            ShoppingListCollectionService.shoppingListCollection.done(collection => {
                this.shoppingListCollection = collection;
                this.listenTo(collection, 'change', this.render);
            });
        },

        initModel: function(options) {
            if (options.productModel) {
                this.model = options.productModel;
            }
        },

        render: function() {
            const totals = this._calcTotals();

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
            const prices = this.model.get('prices');

            _.each(prices, function(unitPrices, productId) {
                this.prices[productId] = PricesHelper.preparePrices(unitPrices);
            }, this);
        },

        _getCurrentShoppingList: function() {
            return this.shoppingListCollection.find({is_current: true});
        },

        _getCurrentLineItems: function() {
            const currentShoppingList = this._getCurrentShoppingList();
            if (!currentShoppingList) {
                return null;
            }
            return _.find(this.model.get('shopping_lists'), {id: currentShoppingList.get('id')}) || null;
        },

        _calcTotals: function() {
            let totals = {
                price: 0,
                quantity: 0
            };

            const lineItems = this._getCurrentLineItems();
            if (_.isNull(lineItems)) {
                return totals;
            }

            totals = _.reduce(lineItems.line_items, function(memo, lineItem) {
                const quantity = lineItem.quantity > 0 ? lineItem.quantity.toString() : '';

                memo.price += PricesHelper.calcTotalPrice(this.prices[lineItem.productId], lineItem.unit, quantity);
                memo.quantity += lineItem.quantity;

                return memo;
            }, totals, this);

            return totals;
        }
    }));

    return ProductTotalPriceView;
});
