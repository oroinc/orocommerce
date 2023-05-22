define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const NumberFormatter = require('orolocale/js/formatter/number');
    const PricesHelper = require('oropricing/js/app/prices-helper');
    const ScrollView = require('orofrontend/js/app/views/scroll-view');
    const FitMatrixView = require('orofrontend/js/app/views/fit-matrix-view');
    const quantityHelper = require('oroproduct/js/app/quantity-helper');
    const numeral = require('numeral');
    const $ = require('jquery');
    const _ = require('underscore');

    const BaseProductMatrixView = BaseView.extend({
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
         * @inheritdoc
         */
        constructor: function BaseProductMatrixView(options) {
            BaseProductMatrixView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            BaseProductMatrixView.__super__.initialize.call(this, options);
            this.initModel(options);
            this.setPrices(options);
            if (_.isDesktop()) {
                if (this.dimension === 1) {
                    this.subview('fitMatrixView', new FitMatrixView({
                        el: this.el
                    }));
                }
            }

            if (this.dimension !== 1 && this.$el.find('[data-scroll-view]').length) {
                this.subview('scrollView', new ScrollView({
                    el: this.el
                }));
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
         * @inheritdoc
         */
        dispose: function() {
            delete this.prices;
            delete this.total;
            delete this.minValue;

            BaseProductMatrixView.__super__.dispose.call(this);
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
            const $cell = $element.closest('[data-index]');
            const index = $cell.data('index');
            const productId = $cell.data('product-id');
            const indexKey = index.row + '.' + index.column;

            const cells = this.total.cells;
            const columns = this.total.columns;
            const rows = this.total.rows;

            const cell = cells[indexKey] = this.getTotal(cells, indexKey);
            const column = columns[index.column] = this.getTotal(columns, index.column);
            column.precision = this.getLineMaxPrecision('column', index);

            const row = rows[index.row] = this.getTotal(rows, index.row);
            row.precision = this.getLineMaxPrecision('row', index);

            if (this.total.precision === void 0) {
                this.total.precision = this.getMatrixMaxPrecision();
            }

            // remove old values
            this.changeTotal(this.total, cell, -1);
            this.changeTotal(column, cell, -1);
            this.changeTotal(row, cell, -1);

            // recalculate cell total
            cell.quantity = NumberFormatter.unformatStrict($element.val());
            const quantity = cell.quantity > 0 ? cell.quantity.toString() : '';
            cell.price = PricesHelper.calcTotalPrice(this.prices[productId], this.model.get('unit'), quantity);

            // add new values
            this.changeTotal(this.total, cell);
            this.changeTotal(column, cell);
            this.changeTotal(row, cell);
        },

        /**
         * @param {string} line
         * @param {object} data
         * @returns {number|null}
         */
        getLineMaxPrecision(line = '', data) {
            const precisions = _.reduce(
                this.$el.find('[data-name="field__quantity"]:enabled'),
                (acc, el) => {
                    const precision = $(el).data('precision');
                    if (
                        $(el).closest('[data-index]').data('index')[line] === data[line] &&
                        precision !== void 0
                    ) {
                        acc.push(precision);
                    }
                    return acc;
                }, []);

            return this.getMaxValue(precisions);
        },

        /**
         * @returns {number|null}
         */
        getMatrixMaxPrecision() {
            const precisions = _.reduce(
                this.$el.find('[data-name="field__quantity"]:enabled'),
                (acc, el) => {
                    const precision = $(el).data('precision');
                    if (precision !== void 0) {
                        acc.push(precision);
                    }
                    return acc;
                }, []);

            return this.getMaxValue(precisions);
        },

        /**
         * @param {array} values
         * @returns {number|null}
         */
        getMaxValue(values) {
            return values.length ? Math.max.apply(null, values) : null;
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
            totals.quantity = numeral(subtotals.quantity).multiply(modifier).add(totals.quantity).value();
            totals.price = numeral(subtotals.price).multiply(modifier).add(totals.price).value();

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
            const val = parseInt(quantity, 10) || 0;

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
            this.$('[data-role="total-quantity"]').text(
                this.formatQuantity(this.total.quantity, this.total.precision)
            );
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
                const $quantity = this.$el.find('[data-' + key + '-quantity="' + index + '"]');
                const $price = this.$el.find('[data-' + key + '-price="' + index + '"]');

                $quantity
                    .toggleClass('valid', total.quantity > 0)
                    .text(this.formatQuantity(total.quantity, total.precision));
                $price
                    .toggleClass('valid', total.price > 0)
                    .text(NumberFormatter.formatCurrency(total.price, total.currency));
            }, this);
        },

        /**
         * @param quantity
         * @param precision
         * @returns {String}
         */
        formatQuantity(quantity, precision) {
            const formatArgs = [quantity];

            if (_.isNumber(precision)) {
                formatArgs.push(precision);
            }

            return quantityHelper.formatQuantity.apply(null, formatArgs);
        },

        /**
         * Check JS max number value
         *
         * @param {Number} value
         * @returns {Boolean}
         * @private
         */
        _isSafeNumber: function(value) {
            return NumberFormatter.unformatStrict(value === '' ? 0 : value) <= Number.MAX_SAFE_INTEGER;
        },

        /**
         * Toggle visibility of clear button
         */
        checkClearButtonVisibility: function() {
            const isFieldsEmpty = _.every(this.$('[data-name="field__quantity"]:enabled'), function(field) {
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
