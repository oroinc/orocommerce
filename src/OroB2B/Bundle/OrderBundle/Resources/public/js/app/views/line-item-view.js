define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var ProductPricesComponent = require('orob2bpricing/js/app/components/product-prices-component');
    var LineItemAbstractView = require('orob2border/js/app/views/line-item-abstract-view');

    /**
     * @export orob2border/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemView
     */
    LineItemView = LineItemAbstractView.extend({
        /**
         * @inheritDoc
         */
        initialize: function() {
            this.options = $.extend(true, {
                selectors: {
                    productType: '.order-line-item-type-product',
                    freeFormType: '.order-line-item-type-free-form',
                    quantityOffers: '.order-line-item-quantity-offers',
                    quantityOfferChoice: '.order-line-item-quantity-offer-choice'
                }
            }, this.options);

            LineItemView.__super__.initialize.apply(this, arguments);

            this.initializeUnitLoader();
        },

        /**
         * @inheritDoc
         */
        handleLayoutInit: function() {
            LineItemView.__super__.handleLayoutInit.apply(this, arguments);

            this.fieldsByName.currency = this.$form
                .find(':input[data-ftid="' + this.$form.attr('name') + '_currency"]');

            this.subtotalFields([
                this.fieldsByName.product,
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit,
                this.fieldsByName.priceValue,
                this.fieldsByName.priceType
            ]);

            this.initTypeSwitcher();
            this.initOffers();
            this.initPrices();
        },

        initPrices: function() {
            this.subview('productPricesComponents', new ProductPricesComponent({
                _sourceElement: this.$el,
                $product: this.fieldsByName.product,
                $priceValue: this.fieldsByName.priceValue,
                $priceType: this.fieldsByName.priceType,
                $productUnit: this.fieldsByName.productUnit,
                $quantity: this.fieldsByName.quantity,
                $currency: this.fieldsByName.currency
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

        initOffers: function() {
            var $choices = this.$el.find(this.options.selectors.quantityOfferChoice + ' input');
            var $quantityOffers = this.$el.find('div' + this.options.selectors.quantityOffers);
            var $priceValue = this.fieldsByName.priceValue;
            var $productUnit = this.fieldsByName.productUnit;
            var $quantity = this.fieldsByName.quantity;
            var $currency = this.fieldsByName.currency;

            $choices.click(_.bind(function(event) {
                var $choice = $(event.target);
                var $checkedChoice = $choices.filter(':checked');
                if ($choice !== $checkedChoice[0]) {
                    $priceValue.val(parseFloat($choice.data('price'))).change();
                    $productUnit.val($choice.data('unit')).change();
                    $quantity.val($choice.val()).change();
                    $currency.val($choice.data('currency')).change();
                    $choices.filter(':checked').prop('checked', false);
                    $choice.prop('checked', true).change();
                    $quantityOffers.change();
                }
            }, this));

            $($priceValue).add($productUnit).add($quantity).add($currency).change(_.bind(function(event) {
                var $checkedChoice = $choices.filter(':checked');
                if ($checkedChoice.length) {
                    $checkedChoice.prop('checked', false).change();
                    $quantityOffers.change();
                }
            }, this));
        },

        /**
         * @inheritDoc
         */
        resetData: function() {
            LineItemView.__super__.resetData.apply(this, arguments);

            if (this.fieldsByName.hasOwnProperty('priceValue')) {
                this.fieldsByName.priceValue.val(null).addClass('matched-price');
            }
        }
    });

    return LineItemView;
});
