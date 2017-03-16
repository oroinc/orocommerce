define(function(require) {
    'use strict';

    var ProductPricesMatrixView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var PricesHelper = require('oropricing/js/app/prices-helper');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductPricesMatrixView = BaseView.extend(_.extend({}, ElementsHelper, {
        autoRender: true,

        elements: {
            fields: '[data-name="field__quantity"]:enabled',
            fieldsColumn: '[data-name="field__quantity"]:enabled',
            totalQty: '[data-role="total-quantity"]',
            totalRowQty: '[data-footer-row-index]',
            totalPrice: '[data-role="total-price"]',
            submitButtons: '[data-shoppingList],[data-toggle="dropdown"]'
        },

        elementsEvents: {
            'fields': ['input', 'updateTotals']
        },

        total: {
            price: 0,
            commonQuantity: 0,
            rowQuantity: 0,
            rowQuantityIndex: 1
        },

        prices: null,

        unit: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ProductPricesMatrixView.__super__.initialize.apply(this, arguments);
            this.setPrices(options);
            this.initializeElements(options);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            delete this.prices;
            delete this.total;
            this.disposeElements();
            ProductPricesMatrixView.__super__.dispose.apply(this, arguments);
        },

        /**
         * Refactoring prices object model
         */
        setPrices: function(options) {
            this.unit = options.unit;
            this.prices = {};

            _.each(options.prices, function(unitPrices, productId) {
                this.prices[productId] = PricesHelper.preparePrices(unitPrices);
            }, this);
        },

        /**
         * Listen input event, calculate total values of quantity and price
         * Prevent enter string

         * @param event
         */
        updateTotals: function(event) {
            _.debounce(_.bind(function(event) {
                var self = this;
                var currentRowId = $(event.currentTarget).closest('[data-row-index]').data('row-index');

                this.total = _.reduce(this.getElement('fields'), function(total, field) {
                    var $this = $(field);
                    var $parent = $this.closest('[data-row-index]');
                    var productId = $parent.data('product-id');

                    if ($parent.data('row-index') === currentRowId) {
                        total.rowQuantity += self.getValue(field);
                    }

                    if (_.isEmpty(field.value) || !_.isNumber(+field.value)) {
                        return total;
                    }

                    total.commonQuantity += self.getValue(field);
                    total.price += PricesHelper.calcTotalPrice(this.prices[productId], this.unit, field.value);

                    return total;
                }, {
                    price: 0,
                    commonQuantity: 0,
                    rowQuantity: 0,
                    rowQuantityIndex: currentRowId
                }, this);

                this.render();
            }, this), 70)(event);
        },

        getValue: function(field) {
            return parseInt(field.value, 10) || 0;
        },

        /**
         * Render actual view
         */
        render: function() {
            this.getElement('submitButtons')
                .toggleClass('disabled', this.total.commonQuantity === 0)
                .data('disabled', this.total.commonQuantity === 0);

            this.getElement('totalQty').text(this.total.commonQuantity);
            this.getElement('totalPrice').text(
                NumberFormatter.formatCurrency(this.total.price)
            );

            this.getElement('totalRowQty')
                .filter('[data-footer-row-index=' + this.total.rowQuantityIndex + ']')
                .text(this.total.rowQuantity);
        }
    }));
    return ProductPricesMatrixView;
});
