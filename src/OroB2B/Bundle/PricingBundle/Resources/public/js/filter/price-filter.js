define([
    'underscore',
    'oro/filter/number-filter'
], function(_, NumberFilter) {
    'use strict';

    var PriceFilter;

    PriceFilter = NumberFilter.extend({
        unitTemplate: _.template($('#price-filter-template').html()),

        /**
         * @inheritDoc
         */
        criteriaValueSelectors: {
            unit: 'input[name="unit"]:last',
            type: 'input[type="hidden"]:last',
            value: 'input[name="value"]'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.emptyValue = {
                unit: (_.isEmpty(this.unitChoices) ? '' : _.first(this.unitChoices).value),
                type: (_.isEmpty(this.choices) ? '' : _.first(this.choices).value),
                value: ''
            };

            return PriceFilter.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _renderCriteria: function() {
            this._checkAppendFilter();
            return PriceFilter.__super__._renderCriteria.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.unitChoices;
            return PriceFilter.__super__.dispose.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.unit, value.unit);
            return PriceFilter.__super__._writeDOMValue.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            var dataValue = PriceFilter.__super__._readDOMValue.apply(this, arguments);
            dataValue.unit = this._getInputValue(this.criteriaValueSelectors.unit);
            return dataValue;
        },

        /**
         * @inheritDoc
         */
        _onClickChoiceValue: function(e) {
            if ($(e.currentTarget).closest('.price-unit-filter').get(0)) {
                $(e.currentTarget).parent().parent().find('li').each(function() {
                    $(this).removeClass('active');
                });
                $(e.currentTarget).parent().addClass('active');

                var parentDiv = $(e.currentTarget).parent().parent().parent();
                var type = $(e.currentTarget).attr('data-value');
                var choiceName = $(e.currentTarget).html();

                var criteriaValues = this.$(this.criteriaValueSelectors.unit).val(type);
                this.fixSelects();
                criteriaValues.trigger('change');
                choiceName += this.caret;
                parentDiv.find('.dropdown-toggle').html(choiceName);

                this._handleEmptyFilter(type);

                e.preventDefault();
            } else {
                return PriceFilter.__super__._onClickChoiceValue.apply(this, arguments);
            }
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
        _appendUnitFilter: function($filter) {
            var value,
                selectedChoiceLabel = '',
                $unitFilter;

            value = _.extend({}, this.emptyValue, this.value);

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

            $unitFilter.append($filter)

            this._appendUnitFilter._appendFilter.call(this, $unitFilter);
        }
    });

    return PriceFilter;
});
