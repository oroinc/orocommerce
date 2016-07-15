/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ProductMinimumQuantityComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductMinimumQuantityComponent = BaseComponent.extend({

        defaultQuantity: 1,

        /**
         * @property {Object}
         */
        options: {
            'quantities':{}
        },

        /**
         * @param {Object} additionalOptions
         */
        initialize: function(additionalOptions) {
            _.extend(this.options, additionalOptions || {});

            this.product = additionalOptions.productModel;

            this.quantityWasChanged = false;

            this.product.on('change:unit', this.updateQuantity, this);
            this.product.on('change:quantity', this.updateQuantityStatus, this);

            this.updateQuantity();
        },

        updateQuantityStatus: function() {
            this.quantityWasChanged = true;
        },

        updateQuantity: function() {
            var minQuantity =  this.options.quantities[this.product.get('unit')];

            if (!this.quantityWasChanged) {
                this.product.set('quantity', minQuantity == undefined ? this.defaultQuantity : minQuantity);
                this.quantityWasChanged = false;
            }
        },

        dispose: function() {
            if (this.disposed) {
              return;
            }

            this.product.off('change:unit', this.updateQuantity, this);
            this.product.off('change:quantity', this.updateQuantityStatus, this);

            ProductMinimumQuantityComponent.__super__.dispose.call(this);
        }

    });

    return ProductMinimumQuantityComponent;
});
