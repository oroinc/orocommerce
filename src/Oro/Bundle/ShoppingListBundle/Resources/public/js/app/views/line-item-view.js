define(function(require) {
    'use strict';

    const BaseProductView = require('oroproduct/js/app/views/base-product-view');
    const ProductQuantityView = require('oroproduct/js/app/views/product-quantity-editable-view');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');

    const LineItemView = BaseProductView.extend({
        elements: _.extend({}, BaseProductView.prototype.elements, {
            quantity: '[data-name="field__quantity"]',
            unit: '[data-name="field__unit"]'
        }),

        lineItemId: null,

        /**
         * @inheritDoc
         */
        constructor: function LineItemView(options) {
            LineItemView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            LineItemView.__super__.initialize.call(this, options);
            this.lineItemId = options.lineItemId;

            if (this.getElement('quantity').length) {
                const productQuantityView = new ProductQuantityView(_.extend({
                    el: this.$el.get(0),
                    model: this.model
                }, options.quantityComponentOptions));

                this.subview('productQuantityView', productQuantityView);
                this.listenTo(productQuantityView, 'product:quantity-unit:update', this.onQuantityUnitChange);
            }
        },

        onQuantityUnitChange: function(data) {
            mediator.trigger('frontend:shopping-list-item-quantity:update', data);

            this.trigger('unit-changed', {
                lineItemId: this.lineItemId,
                product: this.model.get('id'),
                unit: data.value.unit
            });
        }
    });

    return LineItemView;
});
