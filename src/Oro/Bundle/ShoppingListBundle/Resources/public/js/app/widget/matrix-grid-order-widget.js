define(function(require) {
    'use strict';

    var MatrixGridOrderWidget;
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');

    MatrixGridOrderWidget = DialogWidget.extend({
        initialize: function(options) {
            var urlOptions = {
                productId: options.productId
            };

            this.options.url = options.url = routing.generate('oro_shopping_list_frontend_matrix_grid_order', urlOptions);
            this.options.regionEnabled = false;
            this.options.incrementalPosition = false;

            options.dialogOptions = {
                'modal': true,
                'resizable': false,
                'width': '480',
                'autoResize': true,
                'dialogClass': 'matrix-grid-order-dialog'
            };

            MatrixGridOrderWidget.__super__.initialize.apply(this, arguments);
        }
    });

    return MatrixGridOrderWidget;
});
