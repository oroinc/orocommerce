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
            productSelect: '.sale-quoteproduct-product-select input[type="hidden"]',
            productReplacementSelect: '.sale-quoteproduct-product-replacement-select input[type="hidden"]',
            typeSelect: '.sale-quoteproduct-type-select',
            unitsSelect: '.sale-quoteproductitem-productunit-select',
            unitsRoute: 'orob2b_product_unit_product_units',
            addItemButton: '.add-list-item',
            addNotesButton: '.sale-quoteproduct-add-notes-btn',
            removeNotesButton: '.sale-quoteproduct-remove-notes-btn',
            itemsContainer: '.sale-quoteproductitem-collection .oro-item-collection',
            productReplacementContainer: '.sale-quoteproduct-product-select-replacement',
            sellerNotesContainer: '.sale-quoteproduct-notes-seller',
            errorMessage: 'Sorry, unexpected error was occurred',
            units: {}
        },

        /**
         * @property {int}
         */
        typeReplacement : null,

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
        $typeSelect : null,
        
        /**
         * @property {Object}
         */
        $addItemButton : null,

        /**
         * @property {Object}
         */
        $addNotesButton : null,
        
        /**
         * @property {Object}
         */
        $itemsContainer : null,

        /**
         * @property {Object}
         */
        $sellerNotesContainer : null,

        /**
         * @property {Object}
         */
        $productReplacementContainer : null,

        /** 
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.units = _.defaults(this.units, options.units);
            
            this.$container = options._sourceElement;

            this.typeReplacement = options.typeReplacement;

            this.loadingMask = new LoadingMaskView({container: this.$container});
            
            this.$productSelect = this.$container.find(this.options.productSelect);
            this.$typeSelect = this.$container.find(this.options.typeSelect);
            this.$addItemButton = this.$container.find(this.options.addItemButton);
            this.$addNotesButton = this.$container.find(this.options.addNotesButton);
            this.$itemsContainer = this.$container.find(this.options.itemsContainer);
            this.$productReplacementContainer = this.$container.find(this.options.productReplacementContainer);
            this.$sellerNotesContainer = this.$container.find(this.options.sellerNotesContainer);

            this.$container
                .on('change', this.options.productSelect, _.bind(this.onProductChanged, this))
                .on('change', this.options.typeSelect, _.bind(this.onTypeChanged, this))
                .on('click', this.options.addNotesButton, _.bind(this.onAddNotesClick, this))
                .on('click', this.options.removeNotesButton, _.bind(this.onRemoveNotesClick, this))
                .on('content:changed', _.bind(this.onContentChanged, this))
            ;
            
            this.checkAddButton();
        },
        
        checkAddButton: function() {
            this.$productSelect.val() ? this.$addItemButton.show() : this.$addItemButton.hide();
        },
        
        /**
         * Handle Product change
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
         * Handle Type change
         *
         * @param {jQuery.Event} e
         */
        onTypeChanged: function (e) {
            if (this.typeReplacement === parseInt(this.$typeSelect.val())) {
                this.$productReplacementContainer.show();
            } else {
                this.$productReplacementContainer.hide();
            }
        },

        /**
         * Handle Content change
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

        /**
         * Handle Add Notes click
         *
         * @param {jQuery.Event} e
         */
        onAddNotesClick: function (e) {
            this.$sellerNotesContainer.show();
            this.$addNotesButton.css('visibility', 'hidden');
        },

        /**
         * Handle Remove Notes click
         *
         * @param {jQuery.Event} e
         */
        onRemoveNotesClick: function (e) {
            this.$sellerNotesContainer.hide();
            this.$addNotesButton.css('visibility', 'visible');
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
