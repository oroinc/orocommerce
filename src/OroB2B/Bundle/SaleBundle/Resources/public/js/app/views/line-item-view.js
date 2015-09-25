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
            unitsRoute: 'orob2b_product_unit_product_units',
            compactUnits: false,
            addItemButton: '.add-list-item',
            notesContainer: '.quote-lineitem-notes',
            addNotesButton: '.quote-lineitem-notes-add-btn',
            removeNotesButton: '.quote-lineitem-notes-remove-btn',
            itemsCollectionContainer: '.quote-lineitem-collection',
            itemsContainer: '.quote-lineitem-offers-items',
            itemWidget: '.quote-lineitem-offers-item',
            syncClass: 'synchronized',
            productReplacementContainer: '.sale-quoteproduct-product-replacement-select',
            sellerNotesContainer: '.quote-lineitem-notes-seller',
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
         * @property {Object}
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

            this.$el.off();

            LineItemView.__super__.dispose.call(this);
        }
    });

    return LineItemView;
});
