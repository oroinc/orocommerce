define(function(require) {
    'use strict';

    var LineItemTypeComponent;
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2border/js/app/components/line-item-type-component
     * @extends oroui.app.components.base.Component
     * @class orob2border.app.components.LineItemTypeComponent
     */
    LineItemTypeComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                lineItem: '.order-line-item',
                productType: '.order-line-item-type-product',
                freeFormType: '.order-line-item-type-free-form'
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $lineItem: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, this.options, options || {});

            this.$el = options._sourceElement;
            this.$lineItem = this.$el.closest(this.options.selectors.lineItem);

            var self = this;
            var $freeFormType = this.$el.find('a' + this.options.selectors.freeFormType).click(function() {
                self.$lineItem.find('div' + self.options.selectors.productType).hide();
                self.$lineItem.find('div' + self.options.selectors.freeFormType).show();
            });

            var $productType = this.$el.find('a' + this.options.selectors.productType).click(function() {
                self.$lineItem.find('div' + self.options.selectors.freeFormType).hide();
                self.$lineItem.find('div' + self.options.selectors.productType).show();
            });

            if (this.$el.find('div' + self.options.selectors.freeFormType).find('input').val() !== '') {
                $freeFormType.click();
            } else {
                $productType.click();
            }
        }
    });

    return LineItemTypeComponent;
});
