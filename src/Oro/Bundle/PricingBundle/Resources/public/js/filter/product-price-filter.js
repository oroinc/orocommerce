/*global define*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oro/filter/number-range-filter'
], function($, _, __, tools, NumberRangeFilter) {
    'use strict';

    var ProductPriceFilter;

    /**
     * Product price filter
     *
     * @export  oro/filter/product-price-filter
     * @class   oro.filter.ProductPriceFilter
     * @extends oro.filter.NumberRangeFilter
     */
    ProductPriceFilter = NumberRangeFilter.extend({
        /**
         * @property
         */
        unitTemplate: _.template($('#product-price-filter-template').html()),

        /**
         * @property {Array}
         */
        unitChoices: [],

        /**
         * @property {Object}
         */
        criteriaValueSelectors: {
            unit: 'input[name="unit"]',
            type: 'input.name_input'
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            ProductPriceFilter.__super__.initialize.apply(this, arguments);

            _.defaults(this.emptyValue, {
                unit: (_.isEmpty(this.unitChoices) ? '' : _.first(this.unitChoices).value),
                type: (_.isEmpty(this.choices) ? '' : _.first(this.choices).value)
            });

            _.defaults(this.criteriaValueSelectors, ProductPriceFilter.__super__.criteriaValueSelectors);
        },

        /**
         * @inheritDoc
         */
        _renderCriteria: function() {
            this._checkAppendFilter();
            return ProductPriceFilter.__super__._renderCriteria.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.unitChoices;
            return ProductPriceFilter.__super__.dispose.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.unit, value.unit);
            return ProductPriceFilter.__super__._writeDOMValue.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            var dataValue = ProductPriceFilter.__super__._readDOMValue.apply(this, arguments);
            dataValue.unit = this._getInputValue(this.criteriaValueSelectors.unit);
            return dataValue;
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();

            if (this.isEmptyValue()) {
                return this.placeholder;
            }

            var hintValue = ProductPriceFilter.__super__._getCriteriaHint.apply(this, arguments);

            var unitOption = '';
            if (!_.isUndefined(value.unit) && value.unit) {
                unitOption = _.findWhere(this.unitChoices, {value: value.unit}).shortLabel;
            }

            hintValue += ' ' + __('oro.pricing.filter.product_price.per') + ' ' + unitOption;

            return hintValue;
        },

        /**
         * @inheritDoc
         */
        _updateValueField: function() {
            ProductPriceFilter.__super__._updateValueField.apply(this, arguments);

            var valueFrame = this.$('.value-field-frame');
            if (!valueFrame.length) {
                return;
            }

            valueFrame.css('margin-right', 0);

            var type = this.$(this.criteriaValueSelectors.type).val();

            this.$('.product-price-unit-filter-separator').toggle(!this.isEmptyType(type));
        },

        /**
         * @inheritDoc
         */
        _onClickChoiceValue: function(e) {
            var target = $(e.currentTarget);

            if (target.closest('.product-price-unit-filter').get(0)) {
                target.parent().parent().find('li').each(function() {
                    $(this).removeClass('active');
                });
                target.parent().addClass('active');

                var parentDiv = target.parent().parent().parent();
                var type = target.attr('data-value');
                var choiceName = target.html();

                var criteriaValues = this.$(this.criteriaValueSelectors.unit).val(type);
                this.fixSelects();
                criteriaValues.trigger('change');
                choiceName += this.caret;
                parentDiv.find('.dropdown-toggle').html(choiceName);

                this._handleEmptyFilter(type);

                e.preventDefault();
            } else {
                return ProductPriceFilter.__super__._onClickChoiceValue.apply(this, arguments);
            }
        },

        /**
         * @inheritDoc
         */
        _beforeApply: function() {
            this._updateNegativeValue(this._readDOMValue());
            ProductPriceFilter.__super__._beforeApply.apply(this, arguments);
        },

        /**
         * @private
         */
        _checkAppendFilter: function() {
            if (this._appendFilter !== this._appendUnitFilter) {
                this._appendUnitFilter._appendFilter = this._appendFilter;
                this._appendFilter = this._appendUnitFilter;
            }
        },

        /**
         * @private
         */
        _updateNegativeValue: function(value) {
            var currentValue = this._formatRawValue(value);
            var oldValue = tools.deepClone(currentValue);

            currentValue.value = Math.abs(currentValue.value);
            currentValue.value_end = Math.abs(currentValue.value_end);

            if (!tools.isEqualsLoosely(currentValue, oldValue)) {
                //apply new values and filter type
                this._writeDOMValue(currentValue);
            }
        },

        /**
         * @private
         */
        _appendUnitFilter: function($filter) {
            var value = _.extend({}, this.emptyValue, this.value);
            var selectedChoiceLabel = '';
            var $filterValue;
            var $unitFilter;

            if (!_.isEmpty(this.unitChoices)) {
                selectedChoiceLabel = _.find(this.unitChoices, function(choice) {
                    return (choice.value === value.unit);
                }).label;
            }

            $unitFilter = $(this.unitTemplate({
                choices: this.unitChoices,
                selectedChoice: value.unit,
                selectedChoiceLabel: selectedChoiceLabel
            }));

            $filter.addClass('product-price-filter-criteria');
            $filterValue = $filter.find('.filter-value');
            $filterValue.append($unitFilter);

            this._appendUnitFilter._appendFilter.call(this, $filter);
        }
    });

    return ProductPriceFilter;
});
