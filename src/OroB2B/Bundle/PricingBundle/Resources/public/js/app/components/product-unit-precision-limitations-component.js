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
            'product:precision:add mediator': 'onChange'
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
            var self = this;

            _.each(this.getSelects(), function (select) {
                if (self.clearOptions(select) || self.addOptions(select)) {
                    $(select).trigger('change');
                }
            });
        },

        /**
         * Clear options from selects
         *
         * @param {jQuery.Element} select
         *
         * @return {Boolean}
         */
        clearOptions: function (select) {
            var units = this.getUnits(),
                updateRequired = false;

            _.each($(select).find('option'), function (option) {
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
         * @param {jQuery.Element} select
         *
         * @return {Boolean}
         */
        addOptions: function (select) {
            var units = this.getUnits(),
                updateRequired = false;

            _.each(units, function (text, value) {
                if (!$(select).find("option[value='" + value + "']").length) {
                    $(select).append($('<option/>').val(value).text(text));

                    updateRequired = true;
                }
            });

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
            return $(':data(' + this.options.unitsAttribute + ')').data(this.options.unitsAttribute) || {};
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
