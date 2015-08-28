define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
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
            var $freeFormType = this.$el.find('a' + this.options.selectors.freeFormType).click(_.bind(function() {
                this.fieldsByName.product.select2('val', '').change();
                this.$el.find('div' + this.options.selectors.productType).hide();
                this.$el.find('div' + this.options.selectors.freeFormType).show();
            }, this));

            var $productType = this.$el.find('a' + this.options.selectors.productType).click(_.bind(function() {
                var $freeFormTypeContainers = this.$el.find('div' + this.options.selectors.freeFormType);
                $freeFormTypeContainers.find(':input').val('').change();
                $freeFormTypeContainers.hide();
                this.$el.find('div' + this.options.selectors.productType).show();
            }, this));

            if (this.fieldsByName.freeFormProduct.val() !== '') {
                $freeFormType.click();
            } else {
                $productType.click();
            }
        }
    });

    return LineItemView;
});
