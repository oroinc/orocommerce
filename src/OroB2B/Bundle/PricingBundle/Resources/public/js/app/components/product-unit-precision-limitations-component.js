/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ProductUnitPrecisionLimitationsComponent,
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component');

    ProductUnitPrecisionLimitationsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectSelector: "select[name^='orob2b_product[prices]'][name$='[unit]']",
            unitsAttribute: 'units'
        },

        /**
         * @property {Object}
         */
        listen: {
            'product:precision:remove mediator': 'onChange',
            'product:precision:add mediator': 'onChange',
            'product:primary:precision:change mediator': 'onChange'
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('content:changed', _.bind(this.onChange, this));

            this.options._sourceElement.trigger('content:changed');
        },

        /**
         * Change options in selects
         */
        onChange: function () {
            var self = this,
                units = this.getUnits();

            _.each(this.getSelects(), function (select) {
                var $select = $(select);
                var clearChangeRequired = self.clearOptions(units, $select);
                var addChangeRequired = self.addOptions(units, $select);
                if (clearChangeRequired || addChangeRequired) {
                    $select.trigger('change');
                }
            });
        },

        /**
         * Clear options from selects
         *
         * @param {Array} units
         * @param {jQuery.Element} $select
         *
         * @return {Boolean}
         */
        clearOptions: function (units, $select) {
            var updateRequired = false;

            _.each($select.find('option'), function (option) {
                if (!option.value) {
                    return;
                }

                if (!units.hasOwnProperty(option.value)) {
                    option.remove();

                    updateRequired = true;
                }
            });

            return updateRequired;
        },

        /**
         * Add options based on units configuration
         *
         * @param {Array} units
         * @param {jQuery.Element} $select
         *
         * @return {Boolean}
         */
        addOptions: function (units, $select) {
            var updateRequired = false,
                emptyOption = $select.find('option[value=""]');

            if (_.isEmpty(units)) {
                emptyOption.show();
            } else {
                emptyOption.hide();
            }

            _.each(units, function (text, value) {
                if (!$select.find("option[value='" + value + "']").length) {
                    $select.append('<option value="' + value + '">' + text + '</option>');
                    updateRequired = true;
                }
            });

            if ($select.val() == '' && !_.isEmpty(units)) {
                var value = _.keys(units)[0];
                $select.val(value);
                updateRequired = true;
            }

            return updateRequired;
        },

        /**
         * Return selects to update
         *
         * @returns {jQuery.Element}
         */
        getSelects: function () {
            return this.options._sourceElement.find(this.options.selectSelector);
        },

        /**
         * Return units from data attribute
         *
         * @returns {Object}
         */
        getUnits: function () {
            var units = {};
            var attribute = this.options.unitsAttribute;
            _.each($(':data(' + attribute + ')'), function(container){
                var unit = $(container).data(attribute) || {};
                _.each(unit, function(val, key){
                    units[key] = val;
                });
            })
            
            return units;
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            ProductUnitPrecisionLimitationsComponent.__super__.dispose.call(this);
        }
    });

    return ProductUnitPrecisionLimitationsComponent;
});
