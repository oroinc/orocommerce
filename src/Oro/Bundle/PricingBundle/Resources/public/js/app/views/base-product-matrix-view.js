define(function(require) {
    'use strict';

    var BaseProductMatrixView;
    var BaseView = require('oroui/js/app/views/base/view');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var PricesHelper = require('oropricing/js/app/prices-helper');
    var ScrollView = require('orofrontend/js/app/views/scroll-view');
    var FitMatrixView = require('orofrontend/js/app/views/fit-matrix-view');
    var $ = require('jquery');
    var _ = require('underscore');

    BaseProductMatrixView = BaseView.extend({
        autoRender: false,

        optionNames: BaseView.prototype.optionNames.concat([
            'dimension'
        ]),

        events: {
            'input [data-name="field__quantity"]:enabled': '_onQuantityChange',
            'change [data-name="field__quantity"]:enabled': '_onQuantityChange',
            'click [data-role="clear"]': 'clearForm'
        },

        total: null,

        prices: null,

        minValue: 1,

        dimension: null,

        /**
         * @inheritDoc
         */
        constructor: function BaseProductMatrixView() {
            BaseProductMatrixView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            BaseProductMatrixView.__super__.initialize.apply(this, arguments);
            this.initModel(options);
            this.setPrices(options);
            if (_.isDesktop()) {
                if (this.dimension === 1) {
                    this.subview('fitMatrixView', new FitMatrixView({
                        el: this.el
                    }));
                } else {
                    this.subview('scrollView', new ScrollView({
                        el: this.el
                    }));
                }
            }

            this.setDefaultTotals();
            this.updateTotals();
        },

        initModel: function(options) {
            if (options.productModel) {
                this.model = options.productModel;
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            delete this.prices;
            delete this.total;
            delete this.minValue;

            BaseProductMatrixView.__super__.dispose.apply(this, arguments);
        },

        /**
         * Set default data for totals
         */
        setDefaultTotals: function() {
            this.total = {
                price: 0,
                quantity: 0,
                rows: {},
                columns: {},
                cells: {}
            };
        },

        /**
         * Refactoring prices object model
         */
        setPrices: function(options) {
            this.prices = {};

            if (options.prices && !_.isObject(options.prices)) {
                options.prices = JSON.parse(options.prices);
            }

            _.each(options.prices, function(unitPrices, productId) {
                this.prices[productId] = PricesHelper.preparePrices(unitPrices);
            }, this);
        },

        /**
         * Listen input event
         *
         * @param {Event} event
         */
        _onQuantityChange: function(event) {
            if (!this._isSafeNumber(event.currentTarget.value)) {
                event.preventDefault();
                return false;
            }

            this.updateTotal($(event.currentTarget));
            this.checkClearButtonVisibility();
            this.render();
        },

        /**
         * Update all totals
         */
        updateTotals: function() {
            _.each(this.$('[data-name="field__quantity"]:enabled'), function(element) {
                this.updateTotal($(element));
            }, this);
        },

        /**
         * Calculate totals for individual field
         *
         * @param {jQuery} $element
         */
        updateTotal: function($element) {
            var $cell = $element.closest('[data-index]');
            var index = $cell.data('index');
            var productId = $cell.data('product-id');
            var indexKey = index.row + '.' + index.column;

            var cells = this.total.cells;
            var columns = this.total.columns;
            var rows = this.total.rows;

            var cell = cells[indexKey] = this.getTotal(cells, indexKey);
            var column = columns[index.column] = this.getTotal(columns, index.column);
            var row = rows[index.row] = this.getTotal(rows, index.row);

            // remove old values
            this.changeTotal(this.total, cell, -1);
            this.changeTotal(column, cell, -1);
            this.changeTotal(row, cell, -1);

            // recalculate cell total
            cell.quantity = this.getValidQuantity($element.val());
            var quantity = cell.quantity > 0 ? cell.quantity.toString() : '';
            cell.price = PricesHelper.calcTotalPrice(this.prices[productId], this.model.get('unit'), quantity);
            $element.val(quantity);

            // add new values
            this.changeTotal(this.total, cell);
            this.changeTotal(column, cell);
            this.changeTotal(row, cell);
        },

        /**
         * Get total by key
         *
         * @param {Object} totals
         * @param {String} key
         * @return {Object}
         */
        getTotal: function(totals, key) {
            return totals[key] || {
                quantity: 0,
                price: 0
            };
        },

        /**
         * Change totals by subtotals using modifier
         *
         * @param {Object} totals
         * @param {Object} subtotals
         * @param {Number|null} modifier
         */
        changeTotal: function(totals, subtotals, modifier) {
            modifier = modifier || 1;
            totals.quantity += subtotals.quantity * modifier;
            totals.price += subtotals.price * modifier;
            if (NumberFormatter.formatDecimal(totals.price) === 'NaN') {
                totals.price = 0;
            }
        },

        /**
         * Validate quantity value
         *
         * @param {String} quantity
         * @return {Number}
         */
        getValidQuantity: function(quantity) {
            var val = parseInt(quantity, 10) || 0;

            if (_.isEmpty(quantity)) {
                return 0;
            } else {
                return val < this.minValue ? this.minValue : val;
            }
        },

        /**
         * Update totals
         */
        render: function() {
            this.$('[data-role="total-quantity"]').text(this.total.quantity);
            this.$('[data-role="total-price"]').text(
                NumberFormatter.formatCurrency(this.total.price, this.total.currency)
            );

            _.each(_.pick(this.total, 'rows', 'columns'), this.renderSubTotals, this);
        },

        /**
         * Update subtotals
         *
         * @param {Object} totals
         * @param {String} key
         */
        renderSubTotals: function(totals, key) {
            _.each(totals, function(total, index) {
                var $quantity = this.$el.find('[data-' + key + '-quantity="' + index + '"]');
                var $price = this.$el.find('[data-' + key + '-price="' + index + '"]');

                var formattedCurrency = NumberFormatter.formatCurrency(total.price, total.currency);

                $quantity.toggleClass('valid', total.quantity > 0).html(total.quantity);
                $price.toggleClass('valid', total.price > 0).html(formattedCurrency);
            }, this);
        },

        /**
         * Check JS max number value
         *
         * @param {Number} value
         * @returns {Boolean}
         * @private
         */
        _isSafeNumber: function(value) {
            return _.isSafeInteger(parseFloat(value === '' ? 0 : value));
        },

        /**
         * Toggle visibility of clear button
         */
        checkClearButtonVisibility: function() {
            var isFieldsEmpty = _.every(this.$('[data-name="field__quantity"]:enabled'), function(field) {
                return _.isEmpty(field.value);
            });

            this.$('[data-role="clear"]').toggleClass('disabled', isFieldsEmpty);
        },

        /**
         * Clear matrix form fields and totals info
         */
        clearForm: function() {
            this.$('[data-name="field__quantity"]:enabled').filter(function() {
                return this.value.length > 0;
            }).val('').trigger('change');
        }
    });
    return BaseProductMatrixView;
});
