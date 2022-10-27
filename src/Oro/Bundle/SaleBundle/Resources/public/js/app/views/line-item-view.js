define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const BaseView = require('oroui/js/app/views/base/view');
    const UnitsUtil = require('oroproduct/js/app/units-util');
    const BaseModel = require('oroui/js/app/models/base/model');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    require('jquery.validate');

    /**
     * @export orosale/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orosale.app.views.LineItemView
     */
    const LineItemView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            classNotesSellerActive: 'quote-lineitem-notes-seller-active',
            productSelect: '.quote-lineitem-product-select input[type="hidden"]',
            productSkuLabel: '.quote-lineitem-product-sku-label',
            productSkuInput: '.quote-lineitem-product-free-form input[data-name="field__product-sku"]',
            productReplacementSelect: '.quote-lineitem-product-replacement-select input[type="hidden"]',
            offersQuantitySelector: '.quote-lineitem-offers-quantity input',
            offersPriceValueSelector: '.quote-lineitem-offers-price input:first',
            typeSelect: '.quote-lineitem-product-type-select',
            unitsSelect: '.quote-lineitem-offer-unit-select',
            productFreeFormInput: '.quote-lineitem-product-freeform-input',
            productReplacementFreeFormInput: '.quote-lineitem-productreplacement-freeform-input',
            unitsRoute: 'oro_product_unit_product_units',
            compactUnits: false,
            addItemButton: '.add-list-item',
            itemCollection: '.oro-item-collection',
            productSelectLink: '.quote-lineitem-product-select-link',
            freeFormLink: '.quote-lineitem-free-form-link',
            allowEditFreeForm: true,
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
            errorMessage: 'Sorry, an unexpected error has occurred.',
            submitButton: 'button#save-and-transit',
            allUnits: {},
            units: {},
            events: {
                before: 'entry-point:quote:load:before',
                after: 'entry-point:quote:load:after',
                trigger: 'entry-point:quote:trigger'
            }
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
         * @property {Boolean}
         */
        isFreeForm: false,

        /**
         * @property {Object}
         */
        units: {},

        /**
         * @property {Array}
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
         * @inheritdoc
         */
        constructor: function LineItemView(options) {
            LineItemView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!this.model) {
                this.model = new BaseModel();
            }

            this.options = _.defaults(options || {}, this.options);
            this.units = _.defaults(this.units, options.units);
            this.allUnits = _.defaults(this.allUnits, options.allUnits);

            this.typeOffer = options.typeOffer;
            this.typeReplacement = options.typeReplacement;

            this.isFreeForm = options.isFreeForm || false;

            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.delegate('click', '.removeLineItem', this.removeRow);
            this.delegate('click', '.removeRow', this.removeOfferRow);

            this.$productSelect = this.$el.find(this.options.productSelect);
            this.$productReplacementSelect = this.$el.find(this.options.productReplacementSelect);
            this.$typeSelect = this.$el.find(this.options.typeSelect);
            this.$addItemButton = this.$el.find(this.options.addItemButton);
            this.$itemCollection = this.$el.find(this.options.itemCollection);
            this.$itemsContainer = this.$el.find(this.options.itemsContainer);
            this.$productReplacementContainer = this.$el.find(this.options.productReplacementContainer);
            this.$notesContainer = this.$el.find(this.options.notesContainer);
            this.$sellerNotesContainer = this.$el.find(this.options.sellerNotesContainer);
            this.$requestsOnlyContainer = this.$el.find(this.options.requestsOnlyContainer);

            this.$el
                .on('change', this.options.productSelect, this.onProductChanged.bind(this))
                .on('change', this.options.productReplacementSelect, this.onProductChanged.bind(this))
                .on('change', this.options.typeSelect, this.onTypeChanged.bind(this))
                .on('click', this.options.addNotesButton, this.onAddNotesClick.bind(this))
                .on('click', this.options.removeNotesButton, this.onRemoveNotesClick.bind(this))
                .on('click', this.options.freeFormLink, this.onFreeFormLinkClick.bind(this))
                .on('click', this.options.productSelectLink, this.onProductSelectLinkClick.bind(this))
                .on('content:changed', this.onContentChanged.bind(this))
            ;

            this.listenTo(mediator, this.options.events.before, this.disableSubmit);
            this.listenTo(mediator, this.options.events.after, this.enableSubmit);

            this.$typeSelect.trigger('change');

            this.$form = this.$el.closest('form');
            this.$fields = this.$el.find(':input[name]');

            this.fieldsByName = {};
            this.$fields.each((i, field) => {
                if (!this.fieldsByName[this.formFieldName(field)]) {
                    this.fieldsByName[this.formFieldName(field)] = [];
                }

                this.fieldsByName[this.formFieldName(field)].push($(field));
            });

            this.entryPointTriggers([
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit,
                this.fieldsByName.priceValue,
                this.fieldsByName.priceType
            ]);

            this.checkAddButton();
            this.checkAddNotes();
            if (this.isFreeForm && !this.options.allowEditFreeForm) {
                this.setReadonlyState();
            }

            this.updateValidation();
            this.$el.trigger('content:initialized');
        },

        disableSubmit: function() {
            $(this.options.submitButton).prop('disabled', true);
        },

        enableSubmit: function() {
            $(this.options.submitButton).prop('disabled', false);
        },

        /**
         * @param {Object} field
         * @returns {String}
         */
        formFieldName: function(field) {
            let name = '';
            const nameParts = field.name.replace(/.*\[[0-9]+\]/, '').replace(/[\[\]]/g, '_').split('_');
            let namePart;

            for (let i = 0, iMax = nameParts.length; i < iMax; i++) {
                namePart = nameParts[i];
                if (!namePart.length) {
                    continue;
                }
                if (name.length === 0) {
                    name += namePart;
                } else {
                    name += namePart[0].toUpperCase() + namePart.substr(1);
                }
            }
            return name;
        },

        checkAddButton: function() {
            const enabled = Boolean(this.getProductId()) || (this.isFreeForm && this.options.allowEditFreeForm);
            this.$addItemButton.toggleClass('hide', !enabled);
            this.$itemCollection.toggleClass('hide', !enabled);
        },

        removeOfferRow: function() {
            mediator.trigger(this.options.events.trigger);
        },

        removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();

            mediator.trigger(this.options.events.trigger);
        },

        /**
         * @param {jQuery|Array} fields
         */
        entryPointTriggers: function(fields) {
            _.each(fields, function(fields) {
                _.each(fields, function(field) {
                    $(field).attr('data-entry-point-trigger', true);
                });
            });

            mediator.trigger('entry-point:quote:init');
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

            this.updateSkuLabelAndValue();

            const $quantitySelector = this.$el.find(this.options.offersQuantitySelector);
            $quantitySelector.trigger('change');

            mediator.trigger(this.options.events.trigger);
        },

        /**
         * Handle Type change
         *
         * @param {jQuery.Event} e
         */
        onTypeChanged: function(e) {
            const typeValue = parseInt(this.$typeSelect.val());

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
            this.updateValidation();

            const productId = this.getProductId();
            const productUnits = productId ? this.units[productId] : this.allUnits;

            if (!productId || productUnits) {
                this.updateProductUnits(productUnits, force || false);
            } else {
                const self = this;
                const routeParams = {id: productId};

                if (this.options.compactUnits) {
                    routeParams.short = true;
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
                    errorHandlerMessage: __(this.options.errorMessage)
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
            const self = this;

            self.model.set('product_units', data || {});

            const widgets = self.$el.find(self.options.itemWidget);

            $.each(widgets, function(index, widget) {
                const $select = $(widget).find(self.options.unitsSelect);

                if (!force && $select.hasClass(self.options.syncClass)) {
                    return;
                }

                UnitsUtil.updateSelect(self.model, $select);
                $select.addClass(self.options.syncClass);
                $select.data('product-units', self.model.get('product_units'));
                $select.trigger('product-units:change');
            });

            if (force) {
                this.$el.find('select').inputWidget('refresh');
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

            this.clearInputs();

            this.$el.find(this.options.productFormContainer).toggleClass('hide', true);
            this.$el.find(this.options.freeFormContainer).toggleClass('hide', false);

            this.isFreeForm = true;

            this.checkAddButton();
        },

        /**
         * Handle Product Form click
         *
         * @param {jQuery.Event} e
         */
        onProductSelectLinkClick: function(e) {
            e.preventDefault();

            this.clearInputs();

            this.$el.find(this.options.productFormContainer).toggleClass('hide', false);
            this.$el.find(this.options.freeFormContainer).toggleClass('hide', true);

            this.isFreeForm = false;

            this.checkAddButton();
        },

        clearInputs: function() {
            this.$el.find(this.options.productFormContainer)
                .find('input').val('').change()
            ;
            this.$el.find(this.options.freeFormContainer)
                .find('input').val('')
            ;

            const self = this;

            const widgets = this.$el.find(this.options.itemWidget);

            $.each(widgets, function(index, widget) {
                const $priceValue = $(widget).find(self.options.offersPriceValueSelector);

                $priceValue.addClass('matched-price');
            });

            this.updateSkuLabelAndValue();
        },

        updateSkuLabelAndValue: function() {
            const productData = this.$el.find(this.options.productSelect).inputWidget('data') || {};

            this.$el.find(this.options.productSkuLabel).text(productData.sku || '');
            this.$el.find(this.options.productSkuInput).val(productData.sku || '');
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
            const self = this;

            self.$el.find(self.options.productFreeFormInput).rules('add', {
                required: {
                    param: true,
                    depends: function() {
                        return !self.isProductReplacement() && self.isFreeForm;
                    }
                },
                messages: {
                    required: __('oro.sale.quoteproduct.free_form_product.blank')
                }
            });

            self.$el.find(self.options.productReplacementFreeFormInput).rules('add', {
                required: {
                    param: true,
                    depends: function() {
                        return self.isProductReplacement() && self.isFreeForm;
                    }
                },
                messages: {
                    required: __('oro.sale.quoteproduct.free_form_product.blank')
                }
            });

            self.$productSelect.rules('add', {
                required: {
                    param: true,
                    depends: function() {
                        return !self.isProductReplacement() && !self.isFreeForm;
                    }
                },
                messages: {
                    required: __('oro.sale.quoteproduct.product.blank')
                }
            });

            self.$productReplacementSelect.rules('add', {
                required: {
                    param: true,
                    depends: function() {
                        return self.isProductReplacement() && !self.isFreeForm;
                    }
                },
                messages: {
                    required: __('oro.sale.quoteproduct.product.blank')
                }
            });
        },

        /**
         * Disable items update
         */
        setReadonlyState: function() {
            const self = this;

            self.$el.find(self.options.productFreeFormInput).prop('readonly', true);
            self.$el.find(self.options.productSkuInput).prop('readonly', true);
            self.$el.find(self.options.productReplacementFreeFormInput).prop('readonly', true);
            self.$el.find('.removeLineItem').prop('disabled', true);
            self.$el.find('.removeRow').prop('disabled', true);

            const widgets = this.$el.find(self.options.itemWidget);
            $.each(widgets, function(index, widget) {
                $(widget).find(self.options.unitsSelect).prop('readonly', true);
                $(widget).find(self.options.offersPriceValueSelector).prop('readonly', true);
                $(widget).find(self.options.offersQuantitySelector).prop('readonly', true);
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
