define(function(require) {
    'use strict';

    var LineItemView;
    var BaseProductView = require('orob2bproduct/js/app/views/base-product-view');
    var ProductQuantityView = require('orob2bproduct/js/app/views/product-quantity-editable-view');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');

    LineItemView = BaseProductView.extend({
        lineItemId: null,

        initialize: function(options) {
            LineItemView.__super__.initialize.apply(this, arguments);
            this.lineItemId = options.lineItemId;

            var productQuantityView = new ProductQuantityView(_.extend({
                el: this.$el.get(0),
                model: this.model
            }, options.quantityComponentOptions));

            this.subview('productQuantityView', productQuantityView);
            this.listenTo(productQuantityView, 'product:quantity-unit:update', this.onQuantityUnitChange);
        },

        onQuantityUnitChange: function(data) {
            mediator.trigger('frontend:shopping-list-item-quantity:update', data);

            this.trigger('unit-changed', {
                lineItemId: this.lineItemId,
                product: this.model.get('id'),
                unit: data.unit
            });
        }
    });

    return LineItemView;
});
