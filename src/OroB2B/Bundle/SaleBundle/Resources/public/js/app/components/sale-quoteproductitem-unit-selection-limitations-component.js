/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var QuoteProductItemUnitSelectionLimitationsComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        routing = require('routing'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
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

        /** @property {LoadingMaskView|null} */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var containerId = options['containerId'];
            if (!containerId) {
                return;
            }

            this.$container = $(containerId).closest('.row-oro');

            this.$productSelect = this.$container
                .closest('.sale-quoteproduct-widget')
                .find('select.sale-quoteproduct-product-select');
            this.$productSelect.on('change', _.bind(this.onChange, this));
            this.$container.on('content:changed', _.bind(this.onContentChange, this));

            this.loadingMask = new LoadingMaskView({
                container: this.$container
            });
        },

        /**
         * Handle change select
         *
         * @param {jQuery.Event} e
         */
        onChange: function (e) {
            var self = this;
            var productId = self.$productSelect.val();
            self.loadingMask.show();
            if (productId) {
                $.get(routing.generate('orob2b_api_get_product_available_units', {'id': productId}))
                    .done(_.bind(this.updateProductUnits, this));
            } else {
                var data = {successful: true, data: []};
                this.updateProductUnits(data, true);
            }
        },

        /**
         * Handle container content change
         *
         * @param {jQuery.Event} e
         */
        onContentChange: function (e) {
            var allowedUnitsData = this.$container.data('allowedUnitsData');
            if (allowedUnitsData) {
                this.updateProductUnits(allowedUnitsData, false);
            } else {
                this.$productSelect.trigger('change');
            }
        },

        /**
         * Update available ProductUnit select
         *
         * @param {Object} data
         * @param {Boolean} afterLoad
         */
        updateProductUnits: function(data, afterLoad) {
            var self = this;
            if (!data.successful) {
                return;
            }
            var newOptions = data.data;
            var selects = self.$container.find('select.sale-quoteproductitem-productunit-select');

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
            if (afterLoad) {
                self.$container.data('allowedUnitsData', data);
                self.loadingMask.hide();
            }
        },
        /**
         * Escape string for using in regexp
         *
         * @param {String} s
         */
        escapeRegExp: function(s){
            return s.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
        }
    });

    return QuoteProductItemUnitSelectionLimitationsComponent;
});
