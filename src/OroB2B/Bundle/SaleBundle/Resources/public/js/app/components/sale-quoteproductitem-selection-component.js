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
    require('jquery.validate');

    QuoteProductItemSelectionComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            classQuoteProductBlockActive: 'sale-quoteproduct-block-active',
            classNotesSellerActive: 'sale-quoteproduct-notes-seller-active',
            productSelect: '.sale-quoteproduct-product-select input[type="hidden"]',
            productReplacementSelect: '.sale-quoteproduct-product-replacement-select input[type="hidden"]',
            productFreeFormInput: '.sale-quoteproduct-product-freeform-input',
            productReplacementFreeFormInput: '.sale-quoteproduct-productreplacement-freeform-input',
            typeSelect: '.sale-quoteproduct-type-select',
            unitsSelect: '.sale-quoteproductitem-productunit-select',
            unitsRoute: 'orob2b_product_unit_product_units',
            addItemButton: '.add-list-item',
            addNotesButton: '.sale-quoteproduct-add-notes-btn',
            productSelectLink: '.sale-quoteproduct-product-select-link',
            freeFormLink: '.sale-quoteproduct-free-form-link',
            productFormContainer: '.sale-quoteproduct-product-form',
            freeFormContainer: '.sale-quoteproduct-product-free-form',
            fieldsRowContainer: '.fields-row',
            removeNotesButton: '.sale-quoteproduct-remove-notes-btn',
            itemsCollectionContainer: '.sale-quoteproductitem-collection',
            itemsContainer: '.sale-quoteproductitem-collection .oro-item-collection',
            itemWidget: '.sale-quoteproductitem-widget',
            syncClass: 'synchronized',
            productReplacementContainer: '.sale-quoteproduct-product-replacement-row',
            sellerNotesContainer: '.sale-quoteproduct-notes-seller',
            requestsOnlyContainer: '.sale-quoteproductrequest-only',
            errorMessage: 'Sorry, unexpected error was occurred',
            allUnits: {},
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
         * @property {array}
         */
        allUnits: {},

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
            this.allUnits = _.defaults(this.allUnits, options.allUnits);

            this.$el = options._sourceElement;

            this.typeOffer = options.typeOffer;
            this.typeReplacement = options.typeReplacement;

            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.$productSelect = this.$el.find(this.options.productSelect);
            this.$productReplacementSelect = this.$el.find(this.options.productReplacementSelect);
            this.$typeSelect = this.$el.find(this.options.typeSelect);
            this.$addItemButton = this.$el.find(this.options.addItemButton);
            this.$itemsCollectionContainer = this.$el.find(this.options.itemsCollectionContainer);
            this.$itemsContainer = this.$el.find(this.options.itemsContainer);
            this.$productReplacementContainer = this.$el.find(this.options.productReplacementContainer);
            this.$sellerNotesContainer = this.$el.find(this.options.sellerNotesContainer);
            this.$requestsOnlyContainer = this.$el.find(this.options.requestsOnlyContainer);

            this.$el
                .on('change', this.options.productSelect, _.bind(this.onProductChanged, this))
                .on('change', this.options.productReplacementSelect, _.bind(this.onProductChanged, this))
                .on('change', this.options.typeSelect, _.bind(this.onTypeChanged, this))
                .on('click', this.options.addNotesButton, _.bind(this.onAddNotesClick, this))
                .on('click', this.options.removeNotesButton, _.bind(this.onRemoveNotesClick, this))
                .on('click', this.options.freeFormLink, _.bind(this.onFreeFormLinkClick, this))
                .on('click', this.options.productSelectLink, _.bind(this.onProductSelectLinkClick, this))
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

            this.$productReplacementContainer.toggleClass(
                this.options.classQuoteProductBlockActive, this.typeReplacement === typeValue
            );
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
            this.updateValidation();

            var productId = this.getProductId();
            var productUnits = productId ? this.units[productId] : this.allUnits;

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

            var widgets = self.$el.find(self.options.itemWidget);

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
                this.$el.find('select').uniform('update');
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
         * Handle Free Form for Product click
         *
         * @param {jQuery.Event} e
         */
        onFreeFormLinkClick: function(e) {
            e.preventDefault();
            var $rowElem = $(e.target).closest(this.options.fieldsRowContainer);
            $rowElem.find(this.options.productFormContainer)
                .removeClass(this.options.classQuoteProductBlockActive)
                .find('input').val('').change()
            ;
            $rowElem.find(this.options.freeFormContainer).addClass(this.options.classQuoteProductBlockActive);
        },

        /**
         * Handle Product Form click
         *
         * @param {jQuery.Event} e
         */
        onProductSelectLinkClick: function(e) {
            e.preventDefault();
            var $rowElem = $(e.target).closest(this.options.fieldsRowContainer);
            $rowElem.find(this.options.productFormContainer).addClass(this.options.classQuoteProductBlockActive);
            $rowElem.find(this.options.freeFormContainer).removeClass(this.options.classQuoteProductBlockActive);
        },

        /**
         * Get Product Id
         */
        getProductId: function() {
            return this.isProductReplacement() ? this.$productReplacementSelect.val() : this.$productSelect.val();
        },

        /**
         * Check that Product is Replacement
         */
        isProductReplacement: function() {
            return this.typeReplacement === parseInt(this.$typeSelect.val());
        },

        /**
         * Validation for products
         */
        updateValidation: function() {
            var self = this;

            self.$el.find(self.options.productFreeFormInput).rules('add', {
                required: {
                    param: true,
                    depends: function(element) {
                        return !self.isProductReplacement() &&
                            $(element)
                                .closest(self.options.freeFormContainer)
                                .is('.' + self.options.classQuoteProductBlockActive);
                    }
                },
                messages: {
                    required: 'orob2b.sale.quoteproduct.free_form_product.blank'
                }
            });

            self.$el.find(self.options.productReplacementFreeFormInput).rules('add', {
                required: {
                    param: true,
                    depends: function(element) {
                        return self.isProductReplacement() &&
                            $(element)
                                .closest(self.options.freeFormContainer)
                                .is('.' + self.options.classQuoteProductBlockActive);
                    }
                },
                messages: {
                    required: 'orob2b.sale.quoteproduct.free_form_product.blank'
                }
            });

            self.$productSelect.rules('add', {
                required: {
                    param: true,
                    depends: function(element) {
                        return !self.isProductReplacement() && $(element)
                            .closest(self.options.productFormContainer)
                            .is('.' + self.options.classQuoteProductBlockActive);
                    }
                },
                messages: {
                    required: 'orob2b.sale.quoteproduct.product.blank'
                }
            });

            self.$productReplacementSelect.rules('add', {
                required: {
                    param: true,
                    depends: function(element) {
                        return self.isProductReplacement() &&
                            $(element)
                                .closest(self.options.productFormContainer)
                                .is('.' + self.options.classQuoteProductBlockActive);
                    }
                },
                messages: {
                    required: 'orob2b.sale.quoteproduct.product.blank'
                }
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();

            QuoteProductItemSelectionComponent.__super__.dispose.call(this);
        }
    });

    return QuoteProductItemSelectionComponent;
});
