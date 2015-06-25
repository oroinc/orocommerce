/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var QuoteProductItemUnitSelectionLimitationsComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),        
        routing = require('routing'),
        messenger = require('oroui/js/messenger');

    QuoteProductItemUnitSelectionLimitationsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            productSelect:  '.sale-quoteproduct-product-select input[type="hidden"]',
            unitsSelect:    '.sale-quoteproductitem-productunit-select',
            unitsRoute:     'orob2b_product_unit_product_units',
            addItemButton:  '.add-list-item',
            itemsContainer: '.sale-quoteproductitem-collection .oro-item-collection',
            errorMessage:   'Sorry, unexpected error was occurred',
            units: {}
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
         * @property {Object}
         */
        $addItemButton : null,
        
        /**
         * @property {Object}
         */
        $itemsContainer : null,
        
        /** 
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options    = _.defaults(options || {}, this.options);
            this.units      = _.defaults(this.units, options.units);
            
            this.$container = options._sourceElement;
            
            this.loadingMask = new LoadingMaskView({container: this.$container});
            
            this.$productSelect     = this.$container.find(this.options.productSelect);
            this.$addItemButton     = this.$container.find(this.options.addItemButton);
            this.$itemsContainer    = this.$container.find(this.options.itemsContainer);
            
            this.$container
                .on('change', this.options.productSelect, _.bind(this.onProductChanged, this))
                .on('content:changed', _.bind(this.onContentChanged, this))
            ;
            
            this.checkAddButton();
        },
        
        checkAddButton: function() {
            this.$productSelect.val() ? this.$addItemButton.show() : this.$addItemButton.hide();
        },
        
        /**
         * Handle change
         *
         * @param {jQuery.Event} e
         */
        onProductChanged: function (e) {
            this.checkAddButton();
            
            if (this.$itemsContainer.children().length) {
                this.onContentChanged(e);
            }
        },        

        /**
         * Handle change
         *
         * @param {jQuery.Event} e
         */
        onContentChanged: function (e) {
            this.$container.find('select').uniform();
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
                        .append($('<option/>').val(key).text(value))
                    ;
                });
                if (!currentValue && $(select).has('option:first-child')) {
                    currentValue = $(select).find('option:first-child').val();
                }
                $(select).val(currentValue);
                $(select).uniform('update');
            });
        },
        
        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.$container.off();

            QuoteProductItemUnitSelectionLimitationsComponent.__super__.dispose.call(this);
        }
    });

    return QuoteProductItemUnitSelectionLimitationsComponent;
});
