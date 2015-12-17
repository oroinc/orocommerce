define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var ProductPricesComponent = require('orob2bpricing/js/app/components/product-prices-component');
    var ProductUnitComponent = require('orob2bproduct/js/app/components/product-unit-component');
    var SubtotalsListener = require('orob2bpricing/js/app/listener/subtotals-listener');

    /**
     * @export orob2binvoice/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2invoice.app.views.LineItemView
     */
    LineItemView = BaseView.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {
                selectors: {
                    productSelector: '.invoice-line-item-type-product input.select2',
                    quantitySelector: '.invoice-line-item-quantity input',
                    unitSelector: '.invoice-line-item-quantity select',
                    productSku: '.invoice-line-item-sku .invoice-line-item-type-product',
                    productType: '.invoice-line-item-type-product',
                    freeFormType: '.invoice-line-item-type-free-form'
                },
                currency: 'USD'
            }, this.options);
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
            this.delegate('click', '.removeLineItem', this.removeRow);
        },

        /**
         * @inheritDoc
         */
        handleLayoutInit: function() {
            this.$form = this.$el.closest('form');
            this.$fields = this.$el.find(':input[name]');

            this.fieldsByName = {};
            this.$fields.each(_.bind(function(i, field) {
                this.fieldsByName[this.formFieldName(field)] = $(field);
            }, this));

            this.initSubtotalListener();
            this.initUnitLoader();
            this.initTypeSwitcher();
            this.initPrices();
            this.initProduct();
        },

        initSubtotalListener: function() {
            this.fieldsByName.currency = this.$form
                .find(':input[data-ftid="' + this.$form.attr('name') + '_currency"]');

            SubtotalsListener.listen('invoice:changing'[
                this.fieldsByName.product,
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit,
                this.fieldsByName.priceValue,
                this.fieldsByName.priceType
            ]);
        },

        initUnitLoader: function(options) {
            var defaultOptions = {
                _sourceElement: this.$el,
                productSelector: this.options.selectors.productSelector,
                quantitySelector: this.options.selectors.quantitySelector,
                unitSelector: this.options.selectors.unitSelector,
                loadingMaskEnabled: false,
                dropQuantityOnLoad: false,
                defaultValues: this.options.freeFormUnits
            };

            this.subview('productUnitComponent', new ProductUnitComponent(_.extend({}, defaultOptions, options || {})));
        },

        initPrices: function() {
            this.subview('productPricesComponents', new ProductPricesComponent({
                _sourceElement: this.$el,
                $product: this.fieldsByName.product,
                $priceValue: this.fieldsByName.priceValue,
                $priceType: this.fieldsByName.priceType,
                $productUnit: this.fieldsByName.productUnit,
                $quantity: this.fieldsByName.quantity,
                $currency: this.options.currency
            }));
        },

        initTypeSwitcher: function() {
            var $product = this.$el.find('div' + this.options.selectors.productType);
            var $freeForm = this.$el.find('div' + this.options.selectors.freeFormType);

            var showFreeFormType = function() {
                $product.hide();
                $freeForm.show();
            };

            var showProductType = function() {
                $freeForm.hide();
                $product.show();
            };

            $freeForm.find('a' + this.options.selectors.productType).click(_.bind(function() {
                showProductType();
                $freeForm.find(':input').val('').change();
            }, this));

            $product.find('a' + this.options.selectors.freeFormType).click(_.bind(function() {
                showFreeFormType();
                this.fieldsByName.product.select2('val', '').change();
            }, this));

            if (this.fieldsByName.freeFormProduct.val() !== '') {
                showFreeFormType();
            } else {
                showProductType();
            }
        },
        /**
         * @param {Object} field
         * @returns {String}
         */
        formFieldName: function(field) {
            var name = '';
            var nameParts = field.name.replace(/.*\[[0-9]+\]/, '').replace(/[\[\]]/g, '_').split('_');
            var namePart;

            for (var i = 0, iMax = nameParts.length; i < iMax; i++) {
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
        resetData: function() {
            if (this.fieldsByName.hasOwnProperty('quantity')) {
                this.fieldsByName.quantity.val(1);
            }
        },

        initProduct: function() {
            if (this.fieldsByName.product) {
                this.fieldsByName.product.change(_.bind(function() {
                    this.resetData();

                    var data = this.fieldsByName.product.select2('data') || {};
                    this.$el.find(this.options.selectors.productSku).html(data.sku || null);
                }, this));
            }
        },

        removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();
        }
    });

    return LineItemView;
});
