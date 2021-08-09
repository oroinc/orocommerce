define(function(require) {
    'use strict';

    const BaseProductView = require('oroproduct/js/app/views/base-product-view');

    const _ = require('underscore');

    const LineItemOfferView = BaseProductView.extend({
        /**
         * @inheritdoc
         */
        constructor: function LineItemOfferView(options) {
            LineItemOfferView.__super__.constructor.call(this, options);
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

            options.modelAttr = _.defaults(options.modelAttr || {}, {
                sku: this.lineItemModel.get('sku')
            });

            LineItemOfferView.__super__.initialize.call(this, options);
        },

        updateModel: function() {
            if (this.model) {
                this.model.set({
                    id: this.lineItemModel.get('productId'),
                    product_units: this.lineItemModel.get('product_units'),
                    sku: this.lineItemModel.get('sku')
                });
            } else {
                this.modelAttr.id = this.lineItemModel.get('productId');
                this.modelAttr.product_units = this.lineItemModel.get('product_units');
            }
        }
    });

    return LineItemOfferView;
});
