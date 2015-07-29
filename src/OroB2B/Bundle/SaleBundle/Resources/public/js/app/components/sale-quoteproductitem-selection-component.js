/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var QuoteProductItemSelectionComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');

    QuoteProductItemSelectionComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            classNotesSellerActive: 'sale-quoteproduct-notes-seller-active',
            productSelect: '.sale-quoteproduct-product-select input[type="hidden"]',
            productReplacementSelect: '.sale-quoteproduct-product-replacement-select input[type="hidden"]',
            typeSelect: '.sale-quoteproduct-type-select',
            unitsSelect: '.sale-quoteproductitem-productunit-select',
            unitsRoute: 'orob2b_product_unit_product_units',
            addItemButton: '.add-list-item',
            addNotesButton: '.sale-quoteproduct-add-notes-btn',
            removeNotesButton: '.sale-quoteproduct-remove-notes-btn',
            itemsCollectionContainer: '.sale-quoteproductitem-collection',
            itemsContainer: '.sale-quoteproductitem-collection .oro-item-collection',
            itemWidget: '.sale-quoteproductitem-widget',
            syncClass: 'synchronized',
            productReplacementContainer: '.sale-quoteproduct-product-replacement-select',
            sellerNotesContainer: '.sale-quoteproduct-notes-seller',
            requestsOnlyContainer: '.sale-quoteproductrequest-only',
            errorMessage: 'Sorry, unexpected error was occurred',
            units: {}
        },

        /**
         * @property {int}
         */
        typeOffer: null,

        /**
         * @property {int}
         */
        typeReplacement: null,

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
        $productReplacementSelect: null,

        /**
         * @property {Object}
         */
        $typeSelect: null,

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
        $requestsOnlyContainer: null,

        /**
         * @property {Object}
         */
        $sellerNotesContainer: null,

        /**
         * @property {Object}
         */
        $productReplacementContainer: null,

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

            this.typeOffer = options.typeOffer;
            this.typeReplacement = options.typeReplacement;

            this.loadingMask = new LoadingMaskView({container: this.$container});

            this.$productSelect = this.$container.find(this.options.productSelect);
            this.$productReplacementSelect = this.$container.find(this.options.productReplacementSelect);
            this.$typeSelect = this.$container.find(this.options.typeSelect);
            this.$addItemButton = this.$container.find(this.options.addItemButton);
            this.$itemsCollectionContainer = this.$container.find(this.options.itemsCollectionContainer);
            this.$itemsContainer = this.$container.find(this.options.itemsContainer);
            this.$productReplacementContainer = this.$container.find(this.options.productReplacementContainer);
            this.$sellerNotesContainer = this.$container.find(this.options.sellerNotesContainer);
            this.$requestsOnlyContainer = this.$container.find(this.options.requestsOnlyContainer);

            this.$container
                .on('change', this.options.productSelect, _.bind(this.onProductChanged, this))
                .on('change', this.options.productReplacementSelect, _.bind(this.onProductChanged, this))
                .on('change', this.options.typeSelect, _.bind(this.onTypeChanged, this))
                .on('click', this.options.addNotesButton, _.bind(this.onAddNotesClick, this))
                .on('click', this.options.removeNotesButton, _.bind(this.onRemoveNotesClick, this))
                .on('content:changed', _.bind(this.onContentChanged, this))
            ;

            this.$typeSelect.uniform();
            this.$typeSelect.trigger('change');

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
         * Handle Type change
         *
         * @param {jQuery.Event} e
         */
        onTypeChanged: function(e) {
            var typeValue = parseInt(this.$typeSelect.val());

            this.$productReplacementContainer.toggle(this.typeReplacement === typeValue);
            this.$requestsOnlyContainer.toggle(this.typeOffer !== typeValue);

            this.$productSelect.trigger('change');
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
            var isTypeReplacement = this.typeReplacement === parseInt(this.$typeSelect.val());

            return isTypeReplacement ? this.$productReplacementSelect.val() : this.$productSelect.val();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$container.off();

            QuoteProductItemSelectionComponent.__super__.dispose.call(this);
        }
    });

    return QuoteProductItemSelectionComponent;
});
