define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var BaseView = require('oroui/js/app/views/base/view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');
    require('jquery.validate');

    /**
     * @export orob2bsale/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2bsale.app.views.LineItemView
     */
    LineItemView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            classNotesSellerActive: 'quote-lineitem-notes-seller-active',
            productSelect: '.quote-lineitem-product-select input[type="hidden"]',
            productReplacementSelect: '.quote-lineitem-product-replacement-select input[type="hidden"]',
            typeSelect: '.quote-lineitem-product-type-select',
            unitsSelect: '.quote-lineitem-offer-unit-select',
            classQuoteProductBlockActive: 'quote-lineitem-block-active',
            productFreeFormInput: '.quote-lineitem-product-freeform-input',
            productReplacementFreeFormInput: '.quote-lineitem-productreplacement-freeform-input',
            unitsRoute: 'orob2b_product_unit_product_units',
            compactUnits: false,
            addItemButton: '.add-list-item',
            productSelectLink: '.quote-lineitem-product-select-link',
            freeFormLink: '.quote-lineitem-free-form-link',
            productFormContainer: '.quote-lineitem-product-form',
            freeFormContainer: '.quote-lineitem-product-free-form',
            fieldsRowContainer: '.fields-row',
            notesContainer: '.quote-lineitem-notes',
            addNotesButton: '.quote-lineitem-notes-add-btn',
            removeNotesButton: '.quote-lineitem-notes-remove-btn',
            itemsCollectionContainer: '.quote-lineitem-collection',
            itemsContainer: '.quote-lineitem-offers-items',
            itemWidget: '.quote-lineitem-offers-item',
            syncClass: 'synchronized',
            productReplacementContainer: '.quote-lineitem-product-replacement-row',
            sellerNotesContainer: '.quote-lineitem-notes-seller',
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
         * @property {Object}
         */
        units: {},

        /**
         * @property {array}
         */
        allUnits: {},

        /**
         * @property {Object}
         */
        $el: null,

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
        $notesContainer: null,

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

            this.typeOffer = options.typeOffer;
            this.typeReplacement = options.typeReplacement;

            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.delegate('click', '.removeLineItem', this.removeRow);

            this.$productSelect = this.$el.find(this.options.productSelect);
            this.$productReplacementSelect = this.$el.find(this.options.productReplacementSelect);
            this.$typeSelect = this.$el.find(this.options.typeSelect);
            this.$addItemButton = this.$el.find(this.options.addItemButton);
            this.$itemsCollectionContainer = this.$el.find(this.options.itemsCollectionContainer);
            this.$itemsContainer = this.$el.find(this.options.itemsContainer);
            this.$productReplacementContainer = this.$el.find(this.options.productReplacementContainer);
            this.$notesContainer = this.$el.find(this.options.notesContainer);
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
            this.checkAddNotes();
        },

        checkAddButton: function() {
            this.$addItemButton.toggle(Boolean(this.getProductId()));
        },

        removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();
        },

        /**
         * Handle Product change
         *
         * @param {jQuery.Event} e
         */
        onProductChanged: function(e) {
            this.checkAddButton();

            if (this.getProductId() && !this.$itemsContainer.children().length) {
                this.$addItemButton.click();
            }

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
                var routeParams = {'id': productId};

                if (this.options.compactUnits) {
                    routeParams['short'] = true;
                }

                $.ajax({
                    url: routing.generate(this.options.unitsRoute, routeParams),
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
                var firstValue = $(select).find('option:first-child').val();
                if (!currentValue && firstValue) {
                    currentValue = firstValue;
                }
                $(select).val(currentValue);
                if (null === $(select).val() && firstValue) {
                    $(select).val(firstValue);
                }
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
         * Check Add Notes
         */
        checkAddNotes: function() {
            if (this.$sellerNotesContainer.find('textarea').val()) {
                this.$notesContainer.addClass(this.options.classNotesSellerActive);
                this.$sellerNotesContainer.find('textarea').focus();
            }
        },

        /**
         * Handle Add Notes click
         *
         * @param {jQuery.Event} e
         */
        onAddNotesClick: function(e) {
            e.preventDefault();

            this.$notesContainer.addClass(this.options.classNotesSellerActive);
            this.$sellerNotesContainer.find('textarea').focus();
        },

        /**
         * Handle Remove Notes click
         *
         * @param {jQuery.Event} e
         */
        onRemoveNotesClick: function(e) {
            e.preventDefault();

            this.$notesContainer.removeClass(this.options.classNotesSellerActive);
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
                                .hasClass(self.options.classQuoteProductBlockActive);
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
                                .hasClass(self.options.classQuoteProductBlockActive);
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
                            .hasClass(self.options.classQuoteProductBlockActive);
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
                                .hasClass(self.options.classQuoteProductBlockActive);
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

            LineItemView.__super__.dispose.call(this);
        }
    });

    return LineItemView;
});
