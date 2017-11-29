/*global define*/
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ButtonComponent = require('oroworkflow/js/app/components/button-component');
    var StandardConfirmation = require('oroui/js/standart-confirmation');
    var t = require('orotranslation/js/translator');
    var ShoppingListMatrixCreateOrderButtonComponent;

    ShoppingListMatrixCreateOrderButtonComponent = ButtonComponent.extend({
        shoppingListHasEmptyMatrix: false,

        /**
         * @type {Object}
         */
        messages: {
            content: t('oro.shoppinglist.create_order_confirmation.message'),
            title: t('oro.shoppinglist.create_order_confirmation.title'),
            okText: t('oro.shoppinglist.create_order_confirmation.accept_button_title'),
            cancelText: t('oro.shoppinglist.create_order_confirmation.cancel_button_title')
        },

        matrix_selector: '[name=matrix_collection]',
        matrix_quantity_cell_selector: '[name*=quantity]',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ShoppingListMatrixCreateOrderButtonComponent.__super__.initialize.apply(this, arguments);

            this.shoppingListHasEmptyMatrix = options.shopping_list_has_empty_matrix;
        },

        /**
         * @param clickedButton
         * @private
         */
        _onClickButtonEnabledDisplayTypeDialog: function(clickedButton) {
            var self = this;
            var childArgument = arguments;

            var confirmationCallback = function() {
                ShoppingListMatrixCreateOrderButtonComponent.__super__
                    ._onClickButtonEnabledDisplayTypeDialog
                    .apply(self, childArgument);
            };

            this.showConfirmation(confirmationCallback);
        },

        /**
         * @param clickedButton
         * @private
         */
        _onClickButtonEnabledDisplayTypeNotDialog: function(clickedButton) {
            var self = this;
            var childArgument = arguments;

            var confirmationCallback = function() {
                ShoppingListMatrixCreateOrderButtonComponent.__super__
                    ._onClickButtonEnabledDisplayTypeNotDialog
                    .apply(self, childArgument);
            };

            this.showConfirmation(confirmationCallback);
        },

        showConfirmation: function(callback) {
            var confirmModal = new StandardConfirmation(this.messages);

            if (false === this.shoppingListHasEmptyMatrix) {
                callback();

                return;
            }

            confirmModal
                .off('ok')
                .on('ok')
                .open(callback);
        }
    });

    return ShoppingListMatrixCreateOrderButtonComponent;
});
