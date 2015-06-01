/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var QuoteProductItemUnitSelectionLimitationsComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        routing = require('routing'),
        BaseComponent = require('oroui/js/app/components/base/component');

    QuoteProductItemUnitSelectionLimitationsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        $container : null,

        /**
         * @property {Object}
         */
        $productSelect : null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var containerId = options['containerId'];
            if (!containerId) {
                return;
            }

            this.$container = $(containerId);

            this.$productSelect = this.$container.closest('.sale-quote-product-widget').find('select.quoteproduct-product-select');
            this.$productSelect.on('change', _.bind(this.onChange, this));
        },

        /**
         * Handle change select
         */
        onChange: function () {
            var self = this;
            var productId = self.$productSelect.val();
            $.get(routing.generate('orob2b_api_get_product_available_units', {'id': productId}))
                .done(function(data) {
                    if (!data.successful) {
                        return;
                    }
                    var newOptions = data.data;
                    var selects = self.$container.find('.sale-quoteproduct-product-select');

                    $.each(selects, function(index, select) {
                        var currentValue = $(select).val();
                        $(select).empty();
                        $.each(newOptions, function(key, value) {
                            $(select)
                                .append($('<option></option>')
                                .attr('value', key).text(value))
                            ;
                        });
                        $(select).val(currentValue);
                        $(select).uniform('update');
                    });
                });
        }
    });

    return QuoteProductItemUnitSelectionLimitationsComponent;
});
