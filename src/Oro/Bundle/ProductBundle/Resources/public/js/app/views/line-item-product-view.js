define(function(require) {
    'use strict';

    var LineItemProductView;
    var BaseProductView = require('oroproduct/js/app/views/base-product-view');
    var $ = require('jquery');
    var _ = require('underscore');

    LineItemProductView = BaseProductView.extend({
        elements: _.extend({}, BaseProductView.prototype.elements, {
            id: '[data-name="field__product"]',
            unit: '[data-name="field__product-unit"]'
        }),

        modelElements: _.extend({}, BaseProductView.prototype.modelElements, {
            id: 'id'
        })
    });

    return LineItemProductView;
});
