define([
    'tpl-loader!oropricing/templates/product/pricing-filter.html',
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oro/filter/number-range-filter'
], function(unitTemplate, $, _, __, tools, NumberRangeFilter) {
    'use strict';

    /**
     * Product price filter
     *
     * @export  oro/filter/product-price-filter
     * @class   oro.filter.ProductPriceFilter
     * @extends oro.filter.NumberRangeFilter
     */
    const ProductPriceFilter = NumberRangeFilter.extend({
        /**
         * @property
         */
        unitTemplate: unitTemplate,

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
         * @inheritdoc
         */
        constructor: function ProductPriceFilter(options) {
            ProductPriceFilter.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ProductPriceFilter.__super__.initialize.call(this, options);

            if (typeof this.unitTemplate === 'string') {
                this.unitTemplate = _.template($(this.unitTemplate).html());
            }

            _.defaults(this.emptyValue, {
                unit: (_.isEmpty(this.unitChoices) ? '' : _.first(this.unitChoices).value),
                type: (_.isEmpty(this.choices) ? '' : _.first(this.choices).value)
            });

            _.defaults(this.criteriaValueSelectors, ProductPriceFilter.__super__.criteriaValueSelectors);
        },

        /**
         * @inheritdoc
         */
        _renderCriteria: function() {
            this._checkAppendFilter();
            return ProductPriceFilter.__super__._renderCriteria.call(this);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.unitChoices;
            return ProductPriceFilter.__super__.dispose.call(this);
        },

        /**
         * @inheritdoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.unit, value.unit);
            return ProductPriceFilter.__super__._writeDOMValue.call(this, value);
        },

        /**
         * @inheritdoc
         */
        _readDOMValue: function() {
            const dataValue = ProductPriceFilter.__super__._readDOMValue.call(this);
            dataValue.unit = this._getInputValue(this.criteriaValueSelectors.unit);
            return dataValue;
        },

        /**
         * @inheritdoc
         */
        _getCriteriaHint: function(...args) {
            const value = (args.length > 0) ? this._getDisplayValue(args[0]) : this._getDisplayValue();

            if (this.isEmptyValue()) {
                return this.placeholder;
            }

            let hintValue = ProductPriceFilter.__super__._getCriteriaHint.apply(this, args);

            let unitOption = '';
            if (!_.isUndefined(value.unit) && value.unit) {
                unitOption = _.findWhere(this.unitChoices, {value: value.unit}).shortLabel;
            }

            hintValue += ' ' + __('oro.pricing.filter.product_price.per') + ' ' + unitOption;

            return hintValue;
        },

        /**
         * @inheritdoc
         */
        _updateValueField: function() {
            ProductPriceFilter.__super__._updateValueField.call(this);

            const valueFrame = this.$('.value-field-frame');
            if (!valueFrame.length) {
                return;
            }

            valueFrame.css('margin-right', 0);
        },

        /**
         * @inheritdoc
         */
        _onClickChoiceValue: function(e) {
            const target = $(e.currentTarget);

            if (target.closest('.product-price-unit-filter').get(0)) {
                target.parent().parent().find('li').each(function() {
                    $(this).removeClass('active');
                });
                target.parent().addClass('active');

                const parentDiv = target.parent().parent().parent();
                const type = target.attr('data-value');
                let choiceName = target.html();

                const criteriaValues = this.$(this.criteriaValueSelectors.unit).val(type);
                this.fixSelects();
                criteriaValues.trigger('change');
                choiceName += this.caret;
                parentDiv.find('[data-toggle="dropdown"]').html(choiceName);

                this._handleEmptyFilter(type);

                e.preventDefault();
            } else {
                return ProductPriceFilter.__super__._onClickChoiceValue.call(this, e);
            }
        },

        /**
         * @inheritdoc
         */
        _beforeApply: function() {
            this._updateNegativeValue(this._readDOMValue());
            ProductPriceFilter.__super__._beforeApply.call(this);
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
            const currentValue = this._formatRawValue(value);
            const oldValue = tools.deepClone(currentValue);

            currentValue.value = Math.abs(currentValue.value);
            currentValue.value_end = Math.abs(currentValue.value_end);

            if (!tools.isEqualsLoosely(currentValue, oldValue)) {
                // apply new values and filter type
                this._writeDOMValue(this._formatDisplayValue(currentValue));
            }
        },

        /**
         * Return units template data
         * @returns {{choices: [], selectedChoice, selectedChoiceLabel: string}}
         */
        getUnitTemplateData() {
            const value = _.extend({}, this.emptyValue, this.value);
            let selectedChoiceLabel = '';

            if (!_.isEmpty(this.unitChoices)) {
                selectedChoiceLabel = _.find(this.unitChoices, function(choice) {
                    return (choice.value === value.unit);
                }).label;
            }

            return {
                choices: this.unitChoices,
                selectedChoice: value.unit,
                selectedChoiceLabel: selectedChoiceLabel
            };
        },

        /**
         * @private
         */
        _appendUnitFilter: function($filter) {
            if ($filter === '') {
                return this;
            }

            const $unitFilter = $(this.unitTemplate(this.getUnitTemplateData()));

            $filter.addClass('product-price-filter-criteria');
            const $filterValue = $filter.find('.filter-value');
            $filterValue.append($unitFilter);

            this._appendUnitFilter._appendFilter.call(this, $filter);
        }
    });

    return ProductPriceFilter;
});
