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
            totalPrice: '[data-role="total-price"]',
            submitButtons: '[data-shoppingList],[data-toggle="dropdown"]'
        },

        elementsEvents: {
            'fields': ['input', 'setTotal'],
            'fieldsColumn': ['input', 'setTotalColumn']
        },

        total: {
            price: 0,
            quantity: 0,
            quantityColumn: 0
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
        setTotal: function(event) {
            this.total = _.reduce(this.getElement('fields'), function(total, field) {
                if (_.isEmpty(field.value) || !_.isNumber(+field.value)) {
                    return total;
                }

                var productId = $(field).closest('[data-product-id]').data('product-id');

                total.quantity += parseInt(field.value, 10) || 0;
                total.price += PricesHelper.calcTotalPrice(this.prices[productId], this.unit, field.value);

                return total;
            }, {
                price: 0,
                quantity: 0
            }, this);

            this.render();
        },

        /**
         * Render actual view
         */
        render: function() {
            if (this.total.quantity === 0) {
                this.getElement('submitButtons').addClass('disabled').data('disabled', true);
            } else {
                this.getElement('submitButtons').removeClass('disabled').data('disabled', false);
            }

            this.getElement('totalQty').text(this.total.quantity);
            this.getElement('totalPrice').text(
                NumberFormatter.formatCurrency(this.total.price)
            );
        },
        setTotalColumn: function (event) {
            for (var i=2; i<8; i++) {
                var totalColumn  = 0,
                    columnsTotal = $('.matrix-order-widget__grid-footer-total');

                $('.matrix-order-widget__grid>tbody>tr>td:nth-child(' + i + ')').each(function(){
                    totalColumn += parseInt($(this).find('input').val()) || 0;
                });

                $('.matrix-order-widget__grid>tfoot>tr>td:nth-child(' + i + ')').children(columnsTotal).html(totalColumn);

                $(columnsTotal).each(function(){
                    if ($(this).text() == 0) {
                        $(this).removeClass('selected');
                    } else {
                        $(this).addClass('selected');
                    }
                });
            }
        }
    }));
    return ProductPricesMatrixView;
});
