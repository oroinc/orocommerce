define(function(require) {
    'use strict';

    var LineItemView;
    var BaseProductView = require('oroproduct/js/app/views/base-product-view');
    var ProductQuantityView = require('oroproduct/js/app/views/product-quantity-editable-view');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');

    LineItemView = BaseProductView.extend({
        elements: _.extend({}, BaseProductView.prototype.elements, {
            quantity: '[data-name="field__quantity"]',
            unit: '[data-name="field__unit"]'
        }),

        lineItemId: null,

        /**
         * @inheritDoc
         */
        constructor: function LineItemView() {
            LineItemView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            LineItemView.__super__.initialize.apply(this, arguments);
            this.lineItemId = options.lineItemId;

            if (this.getElement('quantity').length) {
                var productQuantityView = new ProductQuantityView(_.extend({
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
