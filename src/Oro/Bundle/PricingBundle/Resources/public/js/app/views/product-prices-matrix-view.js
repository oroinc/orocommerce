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
            'fields': ['input', 'updateTotals']
        },

        total: {
            price: 0,
            quantity: 0,
            row: {
                index: null,
                quantity: 0,
                subtotal: 0
            },
            column: {
                index: null,
                quantity: 0,
                subtotal: 0
            }
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
            _.each(this.getElement('fields'), function(element) {
                this.update(element);
            }, this);
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
         * Listen input event
         *
         * @param event
         */
        updateTotals: _.debounce(function(event) {
            this.update(event.currentTarget);
        }, 150),

        /**
         * Calculate total values of quantity and price
         * Prevent enter string
         *
         * @param element
         */
        update: function(element) {
            var currentIndex = $(element).closest('[data-index]').data('index');

            this.total = _.reduce(this.getElement('fields'), function(total, field) {
                if (_.isEmpty(field.value)) {
                    return total;
                }

                var $this = $(field);
                var $parent = $this.closest('[data-index]');
                var productId = $parent.data('product-id');
                var productPrice = PricesHelper.calcTotalPrice(this.prices[productId], this.unit, field.value);
                var validValue = this.getValidValue(field.value);

                if ($parent.data('index').row === currentIndex.row) {
                    total.row.quantity += validValue;
                    total.row.subtotal += productPrice;
                }

                if ($parent.data('index').column === currentIndex.column) {
                    total.column.quantity += validValue;
                    total.column.subtotal += productPrice;
                }

                $this.val(validValue);

                total.quantity += validValue;
                total.price += productPrice;

                return total;
            }, {
                price: 0,
                quantity: 0,
                row: {
                    index: currentIndex.row,
                    quantity: 0,
                    subtotal: 0
                },
                column: {
                    index: currentIndex.column,
                    quantity: 0,
                    subtotal: 0
                }
            }, this);

            this.render();
        },

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
            this.getElement('totalQty').text(this.total.commonQuantity);
            this.getElement('totalPrice').text(
                NumberFormatter.formatCurrency(this.total.price)
            );

            _.each(
                _.pick(this.total, 'row', 'column'), // Select need entities
                _.bind(this.renderDataEntity, this, {
                    'subtotal': NumberFormatter.formatCurrency // Define formatted properties
                }),
            this);
        },

        /**
         * Render property matrix grid
         *
         * @param {Object} formatters
         * @param {Object} value
         * @param {String} key
         */
        renderDataEntity: function(formatters, value, key) {
            _.each(_.omit(value, 'index'), function(entity, type) {
                this.$el.find('[data-' + key + '-' + type + '="' + value.index + '"]')
                    .toggleClass('valid', entity > 0)
                    .html(_.has(formatters, type) ? formatters[type].call(this, entity) : entity);
            }, this);
        }
    }));
    return ProductPricesMatrixView;
});
