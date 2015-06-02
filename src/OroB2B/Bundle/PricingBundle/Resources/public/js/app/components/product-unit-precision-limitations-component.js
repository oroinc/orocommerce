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
            var units = this.getUnits,
                updateRequired = false;

            $.each(this.getSelects(), function (index, select) {
                _.each($(select).find('option'), function (option) {
                    if (!option.value) {
                        return;
                    }

                    if (!units.hasOwnProperty(option.value)) {
                        option.remove();

                        updateRequired = true;
                    }
                });

                _.each(units, function (text, value) {
                    if (!$(select).find("option[value='" + value + "']").length) {
                        $(select).append($('<option></option>').val(value).text(text));

                        updateRequired = true;
                    }
                });

                if (updateRequired) {
                    $(select).trigger('change');
                }
            });
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
