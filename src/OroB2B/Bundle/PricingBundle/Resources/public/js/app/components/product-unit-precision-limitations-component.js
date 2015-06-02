/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ProductUnitPrecisionLimitationsComponent,
        mediator = require('oroui/js/mediator'),
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component');

    ProductUnitPrecisionLimitationsComponent = BaseComponent.extend({
        /**
         * @property {array}
         */
        units: {},

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var containerId = options['containerId'];
            if (!containerId) {
                return;
            }
            this.$container = $(containerId);
            this.$container.on('content:changed', _.bind(this.onChange, this));
            mediator.on('product:precision:remove', this.onChange, this);
            mediator.on('product:precision:add', this.onChange, this);

            this.$container.trigger('content:changed');
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
        addOptions: function(select) {
            var units = this.getUnits(),
                updateRequired = false;

            _.each(units, function (text, value) {
                if (!$(select).find("option[value='" + value + "']").length) {
                    $(select).append($('<option></option>').val(value).text(text));

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
            return this.$container.find("select[name^='orob2b_product_form[prices]'][name$='[unit]']")
        },

        /**
         * Return units from data attribute
         *
         * @returns {jQuery.Element}
         */
        getUnits: function () {
            return $(':data(units)').data('units') || {};
        }
    });

    return ProductUnitPrecisionLimitationsComponent;
});
