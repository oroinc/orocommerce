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
            this.model = this.model || options.productModel;

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
                width: 'auto',
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

        _onAdoptedFormSubmitClick: function($form) {
            var emptyMatrixAllowed = $form.data('empty-matrix-allowed');

            var isQuantity = $form.find('[data-name="field__quantity"]').filter(function() {
                return this.value.length > 0;
            }).length > 0;

            if (!emptyMatrixAllowed && !isQuantity) {
                var validator = $form.validate();
                validator.errorsFor($form[0]).remove();
                validator.showLabel($form[0], _.__('oro.product.validation.configurable.required'));
                return false;
            }

            return MatrixGridOrderWidget.__super__._onAdoptedFormSubmitClick.apply(this, arguments);
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
