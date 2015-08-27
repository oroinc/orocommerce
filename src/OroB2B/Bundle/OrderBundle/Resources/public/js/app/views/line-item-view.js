define(function(require) {
    'use strict';

    var LineItemView;
    var LineItemAbstractView = require('orob2border/js/app/views/line-item-abstract-view');

    /**
     * @export orob2border/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemView
     */
    LineItemView = LineItemAbstractView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                productType: '.order-line-item-type-product',
                freeFormType: '.order-line-item-type-free-form'
            }
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            LineItemView.__super__.handleLayoutInit.apply(this, arguments);

            this.subtotalFields([
                this.fieldsByName.product,
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit,
                this.fieldsByName.priceValue,
                this.fieldsByName.priceType
            ]);

            this.initTypeSwitcher();
        },

        initTypeSwitcher: function() {
            var self = this;
            var $freeFormType = this.$el.find('a' + this.options.selectors.freeFormType).click(function() {
                self.fieldsByName.product.select2('val', '').change();
                self.$el.find('div' + self.options.selectors.productType).hide();
                self.$el.find('div' + self.options.selectors.freeFormType).show();
            });

            var $productType = this.$el.find('a' + this.options.selectors.productType).click(function() {
                self.fieldsByName.freeFormProduct.val('').change();
                self.$el.find('div' + self.options.selectors.freeFormType).hide();
                self.$el.find('div' + self.options.selectors.productType).show();
            });

            if (this.fieldsByName.freeFormProduct.val() !== '') {
                $freeFormType.click();
            } else {
                $productType.click();
            }
        }
    });

    return LineItemView;
});
