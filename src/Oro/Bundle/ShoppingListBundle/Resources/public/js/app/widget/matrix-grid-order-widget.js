define(function(require) {
    'use strict';

    var MatrixGridOrderWidget;
    var routing = require('routing');
    var FrontendDialogWidget = require('orofrontend/js/app/components/frontend-dialog-widget');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');

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
                width: '480',
                autoResize: true,
                dialogClass: 'matrix-order-widget--dialog'
            };

            this.fullscreenViewOptions = {
                keepAliveOnClose: false,
                popupLabel: null,
                headerContent: true,
                headerContentOptions: {
                    imageUrl: this.model.attributes.productData.imageUrl,
                    title: this.model.attributes.productData.name,
                    subtitle: __('oro.frontend.shoppinglist.matrix_grid_order.item_number') + this.model.attributes.productData.sku
                },
                footerContent: true
            };
            if (_.isMobile()) {
                this.options.fullscreenViewport = {
                    maxScreenType: 'any'
                };
            }

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
