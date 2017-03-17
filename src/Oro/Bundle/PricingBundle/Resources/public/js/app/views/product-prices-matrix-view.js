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
            rowQuantityIndex: 0
        },

        prices: null,

        unit: null,

        minValue: 1,

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
            delete this.unit;
            delete this.minValue;

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
        updateTotals: _.debounce(function(event) {
            var self = this;
            var currentRowId = $(event.currentTarget).closest('[data-row-index]').data('row-index');

            this.total = _.reduce(this.getElement('fields'), function(total, field) {
                var $this = $(field);
                var $parent = $this.closest('[data-row-index]');
                var productId = $parent.data('product-id');
                var validValue = self.getValidValue(field.value);

                if ($parent.data('row-index') === currentRowId) {
                    total.rowQuantity += validValue;
                }

                if (_.isEmpty(field.value)) {
                    return total;
                }

                $this.val(validValue);

                total.commonQuantity += validValue;
                total.price += PricesHelper.calcTotalPrice(self.prices[productId], self.unit, field.value);

                return total;
            }, {
                price: 0,
                commonQuantity: 0,
                rowQuantity: 0,
                rowQuantityIndex: currentRowId
            }, this);

            this.render();
        }, 150),

        getValidValue: function(value) {
            var val = parseInt(value, 10) || 0;

            if (_.isEmpty(value)) {
                return 0;
            } else {
                return val < this.minValue ? this.minValue : val;
            }
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
                .toggleClass('valid', this.total.rowQuantity > 0)
                .html(this.total.rowQuantity);
        }
    }));
    return ProductPricesMatrixView;
});
