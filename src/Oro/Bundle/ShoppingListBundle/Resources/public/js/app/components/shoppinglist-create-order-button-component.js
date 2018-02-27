define(function(require) {
    'use strict';

    var ButtonComponent = require('oroworkflow/js/app/components/button-component');
    var StandardConfirmation = require('oroui/js/standart-confirmation');
    var __ = require('orotranslation/js/translator');
    var ShoppingListCreateOrderButtonComponent;

    ShoppingListCreateOrderButtonComponent = ButtonComponent.extend({
        hasEmptyMatrix: null,

        shoppingListCollection: null,

        lineItemsCount: null,

        /**
         * @type {Object}
         */
        messages: {
            content: __('oro.shoppinglist.create_order_confirmation.message'),
            title: __('oro.shoppinglist.create_order_confirmation.title'),
            okText: __('oro.shoppinglist.create_order_confirmation.accept_button_title'),
            cancelText: __('oro.shoppinglist.create_order_confirmation.cancel_button_title')
        },

        listen: {
            'line-items-init mediator': '_onLineItemsInit'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.hasEmptyMatrix = options.hasEmptyMatrix;
            return ShoppingListCreateOrderButtonComponent.__super__.initialize.apply(this, arguments);
        },

        /**
         * Listen line items init process
         *
         * @param {Array} lineItems
         * @private
         */
        _onLineItemsInit: function(lineItems) {
            this.lineItemsCount = lineItems.filter(function(lineItem) {
                return lineItem.$el.attr('class').indexOf('--configurable') === -1;
            }).length;
        },

        /**
         * @inheritDoc
         */
        _onClickButtonExecutor: function(clickedButton) {
            this.showConfirmation(ShoppingListCreateOrderButtonComponent.__super__
                ._onClickButtonExecutor.bind(this, arguments));
        },

        /**
         * @inheritDoc
         */
        _onClickButtonRedirect: function(clickedButton) {
            this.showConfirmation(ShoppingListCreateOrderButtonComponent.__super__
                ._onClickButtonRedirect
                .bind(this, arguments));
        },

        showConfirmation: function(callback) {
            if (
                (this.hasEmptyMatrix && this.lineItemsCount === 0) || // empty matrix only
                (!this.hasEmptyMatrix) // not empty matrix or it doesn't exist in SL
            ) {
                callback();
                return;
            }

            var confirmModal = new StandardConfirmation(this.messages);
            confirmModal
                .off('ok')
                .on('ok')
                .open(callback);
        }
    });

    return ShoppingListCreateOrderButtonComponent;
});
