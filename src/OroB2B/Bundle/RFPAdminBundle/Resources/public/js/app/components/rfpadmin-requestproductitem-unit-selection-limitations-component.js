/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var RequestProductItemUnitSelectionLimitationsComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),        
        routing = require('routing'),
        messenger = require('oroui/js/messenger');

    RequestProductItemUnitSelectionLimitationsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            productSelect:  'select.rfpadmin-requestproduct-product-select',
            unitsSelect:    'select.rfpadmin-requestproductitem-productunit-select',
            unitsRoute:     'orob2b_product_unit_product_units',
            errorMessage:   'Sorry, unexpected error was occurred'
        },
        
        /**
         * @property {array}
         */
        units: {},
        
        /**
         * @property {Object}
         */
        $container : null,

        /**
         * @property {Object}
         */
        $productSelect : null,

        /** 
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.$container = options._sourceElement;
            
            this.loadingMask = new LoadingMaskView({container: this.$container});
            
            this.$productSelect = this.$container.find(this.options.productSelect);

            this.$container
                .on('change', this.options.productSelect, _.bind(this.onChanged, this))
                .on('content:changed', _.bind(this.onChanged, this))
            ;
        },

        /**
         * Handle change
         *
         * @param {jQuery.Event} e
         */
        onChanged: function (e) {
            var productId = this.$productSelect.val();
            var productUnits = this.units[productId];
            
            if (!productId || productUnits) {
                this.updateProductUnits(productUnits);
            } else {
                var self = this;
                $.ajax({
                    url: routing.generate(this.options.unitsRoute, {'id': productId}),
                    type: 'GET',
                    beforeSend: function () {
                        self.loadingMask.show();
                    },
                    success: function (response) {
                        self.units[productId] = response.units;
                        self.updateProductUnits(response.units);
                    },
                    complete: function () {
                        self.loadingMask.hide();
                    },
                    error: function (xhr) {
                        self.loadingMask.hide();
                        messenger.showErrorMessage(__(self.options.errorMessage), xhr.responseJSON);
                    }
                });
            }
        },

        /**
         * Update available ProductUnit select
         *
         * @param {Object} data
         */
        updateProductUnits: function(data) {
            var self = this;
            
            var units = data || {};
            
            var selects = self.$container.find(self.options.unitsSelect);
            $.each(selects, function(index, select) {
                var currentValue = $(select).val();
                $(select).empty();
                $.each(units, function(key, value) {
                    $(select)
                        .append($('<option></option>')
                        .attr('value', key).text(value))
                    ;
                });
                $(select).val(currentValue);
                $(select).uniform('update');
            });
        },
        
        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.$container.off();

            RequestProductItemUnitSelectionLimitationsComponent.__super__.dispose.call(this);
        }
    });

    return RequestProductItemUnitSelectionLimitationsComponent;
});
