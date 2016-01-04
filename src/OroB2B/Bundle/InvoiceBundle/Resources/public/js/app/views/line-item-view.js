define(function (require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var ProductPricesComponent = require('orob2bpricing/js/app/components/product-prices-component');
    var ProductUnitComponent = require('orob2bproduct/js/app/components/product-unit-component');
    var SubtotalsListener = require('orob2bpricing/js/app/listener/subtotals-listener');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var mediator = require('oroui/js/mediator');

    /**
     * @export orob2binvoice/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2invoice.app.views.LineItemView
     */
    LineItemView = BaseView.extend({
        priceTypes: {
            'BUNDLE': 20,
            'UNIT': 10
        },

        pricesComponent: {},

        /**
         * @inheritDoc
         */
        initialize: function (options) {
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
            this.$el.on('click', '.removeLineItem', _.bind(this.removeRow, this));
        },

        setTotalPrice: function () {
            var totalPrice;
            if (!this.fieldsByName.priceValue) {
                return;
            }

            totalPrice = +this.fieldsByName.priceValue.val();
            if (+this.fieldsByName.priceType.val() === this.priceTypes.UNIT) {
                totalPrice *= +this.fieldsByName.quantity.val();
            }

            this.$el.find('.invoice-line-item-total-price')
                .text(NumberFormatter.formatCurrency(totalPrice, this.pricesComponent.getCurrency()));
        },

        /**
         * @inheritDoc
         */
        handleLayoutInit: function () {
            this.$form = this.$el.closest('form');
            this.$fields = this.$el.find(':input[name]');

            this.fieldsByName = {};
            this.$fields.each(_.bind(function (i, field) {
                this.fieldsByName[this.formFieldName(field)] = $(field);
            }, this));

            this.initSubtotalListener();
            this.initUnitLoader();
            this.initTypeSwitcher();
            this.initPrices();
            this.initProduct();
            this.initTotalPriceListener();
        },

        initTotalPriceListener: function () {
            var self = this;
            this.setTotalPrice();
            this.pricesComponent.on('currency:changed', _.bind(this.setTotalPrice, this));

            setTimeout(function () {
                _.each([
                        self.fieldsByName.quantity,
                        self.fieldsByName.productUnit,
                        self.fieldsByName.priceValue,
                        self.fieldsByName.priceType
                    ], function (field) {
                        field.on('change', _.bind(self.setTotalPrice, self));
                    }
                );
            }, 100);
        },

        initSubtotalListener: function () {
            this.fieldsByName.currency = this.$form
                .find(':input[data-ftid="' + this.$form.attr('name') + '_currency"]');

            SubtotalsListener.listen('invoice:changing', [
                this.fieldsByName.product,
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit,
                this.fieldsByName.priceValue,
                this.fieldsByName.priceType
            ]);


        },

        initUnitLoader: function (options) {
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

        initPrices: function () {
            this.pricesComponent = new ProductPricesComponent({
                _sourceElement: this.$el,
                $product: this.fieldsByName.product,
                $priceValue: this.fieldsByName.priceValue,
                $priceType: this.fieldsByName.priceType,
                $productUnit: this.fieldsByName.productUnit,
                $quantity: this.fieldsByName.quantity,
                $currency: this.options.currency
            });

            this.subview('productPricesComponents', this.pricesComponent);
        },

        initTypeSwitcher: function () {
            var $product = this.$el.find('div' + this.options.selectors.productType);
            var $freeForm = this.$el.find('div' + this.options.selectors.freeFormType);

            var showFreeFormType = function () {
                $product.hide();
                $freeForm.show();
            };

            var showProductType = function () {
                $freeForm.hide();
                $product.show();
            };

            $freeForm.find('a' + this.options.selectors.productType).click(_.bind(function () {
                showProductType();
                $freeForm.find(':input').val('').change();
            }, this));

            $product.find('a' + this.options.selectors.freeFormType).click(_.bind(function () {
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
        formFieldName: function (field) {
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

        resetData: function () {
            if (this.fieldsByName.hasOwnProperty('quantity')) {
                this.fieldsByName.quantity.val(1);
            }
        },

        initProduct: function () {
            if (this.fieldsByName.product) {
                this.fieldsByName.product.change(_.bind(function () {
                    this.resetData();

                    var data = this.fieldsByName.product.select2('data') || {};
                    this.$el.find(this.options.selectors.productSku).html(data.sku || null);
                }, this));
            }
        },

        removeRow: function () {
            this.$el.trigger('content:remove');
            mediator.trigger('line-items-subtotals:update');
            this.remove();
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }

            mediator.off('line-items-subtotals:update', this.setTotalPrice, this);
            mediator.off('pricing:update-currency', this.setTotalPrice, this);

            ProductPricesComponent.__super__.dispose.call(this);
        }
    });

    return LineItemView;
});
