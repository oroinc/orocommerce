define(function(require) {
    'use strict';

    var MatrixGridOrderWidget;
    var routing = require('routing');
    var FrontendDialogWidget = require('orofrontend/js/app/components/frontend-dialog-widget');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');

    MatrixGridOrderWidget = FrontendDialogWidget.extend({
        optionNames: FrontendDialogWidget.prototype.optionNames.concat([
            'shoppingListId'
        ]),

        shoppingListId: null,

        initialize: function(options) {
            var urlOptions = {
                productId: this.model.get('id'),
                shoppingListId: this.shoppingListId
            };

            options.url = routing.generate('oro_shopping_list_frontend_matrix_grid_order', urlOptions);
            options.preventModelRemoval = true;
            options.regionEnabled = false;
            options.incrementalPosition = false;
            options.dialogOptions = {
                modal: true,
                title: null,
                resizable: false,
                width: '500',
                autoResize: true,
                dialogClass: 'matrix-order-widget--dialog'
            };
            options.initLayoutOptions = {
                productModel: this.model
            };

            this.fullscreenViewOptions = {
                popupLabel: null,
                headerContent: true,
                footerContentOptions: {},
                headerContentOptions: {
                    imageUrl: this.model.get('imageUrl'),
                    title: this.model.get('name'),
                    subtitle: _.__('oro.frontend.shoppinglist.matrix_grid_order.item_number') +
                        ': ' + this.model.get('sku')
                }
            };
            this.options.fullscreenViewport = {
                isMobile: true
            };

            MatrixGridOrderWidget.__super__.initialize.apply(this, arguments);
        },

        _onContentLoad: function(content) {
            if (_.isObject(content)) {
                mediator.trigger('shopping-list:line-items:update-response', this.model, content);
                this.remove();
            } else {
                return MatrixGridOrderWidget.__super__._onContentLoad.apply(this, arguments);
            }
        }
    });

    return MatrixGridOrderWidget;
});
