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

        isConfirmationNeeded: function() {
            let skipConfirm;

            try {
                skipConfirm = !mediator.execute('shoppinglist:hasEmptyMatrix');
            } catch (e) {
                skipConfirm = !this.hasEmptyMatrix;
            }

            return skipConfirm;
        }
    });

    return ShoppingListCreateOrderButtonComponent;
});
