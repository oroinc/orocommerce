define(function(require) {
    'use strict';

    const routing = require('routing');
    const FrontendDialogWidget = require('orofrontend/js/app/components/frontend-dialog-widget');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');

    const MatrixGridOrderWidget = FrontendDialogWidget.extend({
        optionNames: FrontendDialogWidget.prototype.optionNames.concat([
            'shoppingListId'
        ]),

        shoppingListId: null,

        /**
         * @inheritdoc
         */
        constructor: function MatrixGridOrderWidget(options) {
            MatrixGridOrderWidget.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.model = this.model || options.productModel;

            const urlOptions = {
                productId: this.model.get('id'),
                shoppingListId: this.shoppingListId
            };

            if (_.isDesktop()) {
                options.actionsEl = '.product-totals';
                options.moveAdoptedActions = false;
            }
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

            MatrixGridOrderWidget.__super__.initialize.call(this, options);
        },

        _onAdoptedFormSubmitClick: function($form, widget) {
            const emptyMatrixAllowed = $form.data('empty-matrix-allowed');

            const isQuantity = $form.find('[data-name="field__quantity"]').filter(function() {
                return this.value.length > 0;
            }).length > 0;

            if (!emptyMatrixAllowed && !isQuantity) {
                const validator = $form.validate();
                validator.errorsFor($form[0]).remove();
                validator.showLabel($form[0], _.__('oro.product.validation.configurable.required'));
                return false;
            }

            return MatrixGridOrderWidget.__super__._onAdoptedFormSubmitClick.call(this, $form, widget);
        },

        _onContentLoad: function(content) {
            if (_.isObject(content)) {
                mediator.trigger('shopping-list:line-items:update-response', this.model, content);
                this.remove();
            } else {
                return MatrixGridOrderWidget.__super__._onContentLoad.call(this, content);
            }
        }
    });

    return MatrixGridOrderWidget;
});
