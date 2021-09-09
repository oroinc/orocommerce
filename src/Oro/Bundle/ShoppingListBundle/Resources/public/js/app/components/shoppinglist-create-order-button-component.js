define(function(require) {
    'use strict';

    const ButtonComponent = require('oroworkflow/js/app/components/button-component');
    const StandardConfirmation = require('oroui/js/standart-confirmation');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');

    const ShoppingListCreateOrderButtonComponent = ButtonComponent.extend({
        hasEmptyMatrix: null,

        shoppingListCollection: null,

        /**
         * Not used anymore
         * @deprecated
         */
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

        /**
         * @inheritdoc
         */
        constructor: function ShoppingListCreateOrderButtonComponent(options) {
            ShoppingListCreateOrderButtonComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.hasEmptyMatrix = options.hasEmptyMatrix;
            return ShoppingListCreateOrderButtonComponent.__super__.initialize.call(this, options);
        },

        /**
         * Listen line items init process
         *
         * @param {Array} lineItems
         * @deprecated
         * @private
         */
        _onLineItemsInit: function(lineItems) {
            this.lineItemsCount = lineItems.filter(function(lineItem) {
                return lineItem.$el.attr('class').indexOf('--configurable') === -1;
            }).length;
        },

        /**
         * @inheritdoc
         */
        _onClickButtonExecutor: function(clickedButton) {
            this.showConfirmation(ShoppingListCreateOrderButtonComponent.__super__
                ._onClickButtonExecutor.bind(this, clickedButton));
        },

        /**
         * @inheritdoc
         */
        _onClickButtonRedirect: function(clickedButton) {
            this.showConfirmation(ShoppingListCreateOrderButtonComponent.__super__
                ._onClickButtonRedirect
                .bind(this, clickedButton));
        },

        showConfirmation: function(callback) {
            if (this.isConfirmationNeeded()) {
                callback();
                return;
            }

            const confirmModal = new StandardConfirmation(this.messages);
            confirmModal
                .off('ok')
                .on('ok')
                .open(callback);
        },

        /**
         * @return {boolean}
         */
        isConfirmationNeeded: function() {
            let skipConfirm;

            try {
                skipConfirm = !mediator.execute('shoppinglist:hasEmptyMatrix');
            } catch (e) {
                // handler isn't defined in mediator, check empty matrix in old way
                skipConfirm = !!this.hasEmptyMatrix;
            }

            return skipConfirm;
        }
    });

    return ShoppingListCreateOrderButtonComponent;
});
