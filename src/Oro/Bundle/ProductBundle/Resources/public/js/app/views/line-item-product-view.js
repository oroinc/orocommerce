define(function(require) {
    'use strict';

    const BaseProductView = require('oroproduct/js/app/views/base-product-view');
    const _ = require('underscore');

    const LineItemProductView = BaseProductView.extend({
        elements: _.extend({}, BaseProductView.prototype.elements, {
            id: '[data-name="field__product"]',
            quantity: '[data-name="field__quantity"]:first',
            unit: '[data-name="field__product-unit"]'
        }),

        modelElements: _.extend({}, BaseProductView.prototype.modelElements, {
            id: 'id'
        }),

        /**
         * @inheritdoc
         */
        constructor: function LineItemProductView(options) {
            LineItemProductView.__super__.constructor.call(this, options);
        }
    });

    return LineItemProductView;
});
