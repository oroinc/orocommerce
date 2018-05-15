define(function(require) {
    'use strict';

    var LineItemOfferView;
    var BaseProductView = require('oroproduct/js/app/views/base-product-view');

    LineItemOfferView = BaseProductView.extend({
        /**
         * @inheritDoc
         */
        constructor: function LineItemOfferView() {
            LineItemOfferView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el.trigger('options:set:lineItemModel', options);
            this.deferredInitializeCheck(options, ['lineItemModel']);
        },

        deferredInitialize: function(options) {
            this.lineItemModel = options.lineItemModel;
            this.lineItemModel.on('change', this.updateModel, this);
            this.updateModel();
            LineItemOfferView.__super__.initialize.apply(this, arguments);
        },

        updateModel: function() {
            if (this.model) {
                this.model.set({
                    id: this.lineItemModel.get('productId'),
                    product_units: this.lineItemModel.get('product_units')
                });
            } else {
                this.modelAttr.id = this.lineItemModel.get('productId');
                this.modelAttr.product_units = this.lineItemModel.get('product_units');
            }
        }
    });

    return LineItemOfferView;
});
