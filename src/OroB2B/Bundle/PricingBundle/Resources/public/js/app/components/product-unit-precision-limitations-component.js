/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ProductUnitPrecisionLimitationsComponent,
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component');

    ProductUnitPrecisionLimitationsComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var containerId = options['containerId'];
            if (!containerId) {
                return;
            }
            this.$container = $(containerId);

            mediator.on('product:precision:remove', this.removeOptions, this);
            mediator.on('product:precision:add', this.addOptions, this);
        },

        /**
         * Ask options on remove event
         *
         * @param {Object} data with structure {value: value, text: text}
         */
        removeOptions: function (data) {
            var contentChanged = false;

            $.each(this.getSelects(), function (index, select) {
                $(select).find("option[value='" + data.value + "']").remove();

                contentChanged = true;
            });

            this.triggerContentChanged(contentChanged);
        },

        /**
         * Add options on add event
         *
         * @param {Object} data with structure {value: value, text: text}
         */
        addOptions: function (data) {
            var contentChanged = false;

            $.each(this.getSelects(), function (index, select) {
                if (!$(select).find("option[value='" + data.value + "']").length) {
                    $(select).append($('<option></option>').val(data.value).text(data.text));

                    contentChanged = true

                }
            });

            this.triggerContentChanged(contentChanged);
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
         * Triggers content update if needed
         *
         * @param {Boolean} contentChanged
         */
        triggerContentChanged: function (contentChanged) {
            if (!contentChanged) {
                return;
            }

            this.$container.trigger('content:changed');
        }
    });

    return ProductUnitPrecisionLimitationsComponent;
});
