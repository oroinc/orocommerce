/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var OrderProductItemSelectionComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');

    OrderProductItemSelectionComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            classNotesSellerActive: 'order-orderproduct-notes-seller-active',
            productSelect: '.order-orderproduct-product-select input[type="hidden"]',
            unitsSelect: '.order-orderproductitem-productunit-select',
            unitsRoute: 'orob2b_product_unit_product_units',
            addItemButton: '.add-list-item',
            addNotesButton: '.order-orderproduct-add-notes-btn',
            removeNotesButton: '.order-orderproduct-remove-notes-btn',
            itemsCollectionContainer: '.order-orderproductitem-collection',
            itemsContainer: '.order-orderproductitem-collection .oro-item-collection',
            itemWidget: '.order-orderproductitem-widget',
            syncClass: 'synchronized',
            sellerNotesContainer: '.order-orderproduct-notes-seller',
            errorMessage: 'Sorry, unexpected error was occurred',
            units: {}
        },

        /**
         * @property {array}
         */
        units: {},

        /**
         * @property {Object}
         */
        $container: null,

        /**
         * @property {Object}
         */
        $productSelect: null,

        /**
         * @property {Object}
         */
        $addItemButton: null,

        /**
         * @property {Object}
         */
        $itemsContainer: null,

        /**
         * @property {Object}
         */
        $itemsCollectionContainer: null,

        /**
         * @property {Object}
         */
        $sellerNotesContainer: null,

        /**
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.units = _.defaults(this.units, options.units);

            this.$container = options._sourceElement;

            this.loadingMask = new LoadingMaskView({container: this.$container});

            this.$productSelect = this.$container.find(this.options.productSelect);
            this.$addItemButton = this.$container.find(this.options.addItemButton);
            this.$itemsCollectionContainer = this.$container.find(this.options.itemsCollectionContainer);
            this.$itemsContainer = this.$container.find(this.options.itemsContainer);
            this.$sellerNotesContainer = this.$container.find(this.options.sellerNotesContainer);

            this.$container
                .on('change', this.options.productSelect, _.bind(this.onProductChanged, this))
                .on('click', this.options.addNotesButton, _.bind(this.onAddNotesClick, this))
                .on('click', this.options.removeNotesButton, _.bind(this.onRemoveNotesClick, this))
                .on('content:changed', _.bind(this.onContentChanged, this))
            ;

            this.checkAddButton();
        },

        checkAddButton: function() {
            this.$addItemButton.toggle(Boolean(this.getProductId()));
        },

        /**
         * Handle Product change
         *
         * @param {jQuery.Event} e
         */
        onProductChanged: function(e) {
            this.checkAddButton();

            if (this.$itemsContainer.children().length) {
                this.updateContent(true);
            }
        },

        /**
         * Handle Content change
         *
         * @param {jQuery.Event} e
         */
        onContentChanged: function(e) {
            this.updateContent(false);
        },

        /**
         * @param {Boolean} force
         */
        updateContent: function(force) {
            var productId = this.getProductId();
            var productUnits = this.units[productId];

            if (!productId || productUnits) {
                this.updateProductUnits(productUnits, force || false);
            } else {
                var self = this;
                $.ajax({
                    url: routing.generate(this.options.unitsRoute, {'id': productId}),
                    type: 'GET',
                    beforeSend: function() {
                        self.loadingMask.show();
                    },
                    success: function(response) {
                        self.units[productId] = response.units;
                        self.updateProductUnits(response.units, force || false);
                    },
                    complete: function() {
                        self.loadingMask.hide();
                    },
                    error: function(xhr) {
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
         * @param {Boolean} force
         */
        updateProductUnits: function(data, force) {
            var self = this;

            var units = data || {};

            var widgets = self.$container.find(self.options.itemWidget);

            $.each(widgets, function(index, widget) {
                var select = $(widget).find(self.options.unitsSelect);

                if (!force && $(select).hasClass(self.options.syncClass)) {
                    return;
                }

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
                $(select).addClass(self.options.syncClass);

                if (!force) {
                    $(widget).find('select').uniform('update');
                }
            });

            if (force) {
                this.$container.find('select').uniform('update');
            }
        },

        /**
         * Handle Add Notes click
         *
         * @param {jQuery.Event} e
         */
        onAddNotesClick: function(e) {
            e.preventDefault();

            this.$itemsCollectionContainer.addClass(this.options.classNotesSellerActive);
            this.$sellerNotesContainer.find('textarea').focus();
        },

        /**
         * Handle Remove Notes click
         *
         * @param {jQuery.Event} e
         */
        onRemoveNotesClick: function(e) {
            e.preventDefault();

            this.$itemsCollectionContainer.removeClass(this.options.classNotesSellerActive);
            this.$sellerNotesContainer.find('textarea').val('');
        },

        /**
         * Get Product Id
         */
        getProductId: function() {
            return this.$productSelect.val();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$container.off();

            OrderProductItemSelectionComponent.__super__.dispose.call(this);
        }
    });

    return OrderProductItemSelectionComponent;
});
