define(function(require) {
    'use strict';

    var MatrixGridOrderWidget;
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');

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
                'title': null,
                'resizable': false,
                'width': '480',
                'autoResize': true,
                'dialogClass': 'matrix-order-widget--dialog'
            };

            MatrixGridOrderWidget.__super__.initialize.apply(this, arguments);
        },

        _onContentLoad: function(content) {
            if (_.has(content, 'redirectUrl')) {
                mediator.execute('redirectTo', {url: content.redirectUrl}, {redirect: true});
                return;
            }

            MatrixGridOrderWidget.__super__._onContentLoad.apply(this, arguments);
        }
    });

    return MatrixGridOrderWidget;
});
