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
                    freeFormType: '.order-line-item-type-free-form'
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

            $freeForm.find('a').click(_.bind(function() {
                showProductType();
                $freeForm.find(':input').val('').change();
            }, this));

            $product.find('a').click(_.bind(function() {
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
         * @inheritDoc
         */
        resetData: function() {
            LineItemView.__super__.resetData.apply(this, arguments);

            if (this.fieldsByName.hasOwnProperty('priceValue')) {
                this.fieldsByName.priceValue.val(null);
            }
        }
    });

    return LineItemView;
});
