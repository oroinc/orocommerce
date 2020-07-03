define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const DeleteConfirmationView = require('oroui/js/delete-confirmation');

    const ShoppingListSetDefaultLinkView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'dialogClass', 'dialogTitle', 'dialogOkText', 'dialogContentText', 'successText',
            'shoppingListSetDefaultUrl'
        ]),

        /**
         * View events
         *
         * @property {Object}
         */
        events: {
            click: '_onClick'
        },

        /**
         * @property {String}
         */
        dialogClass: 'modal shopping-list-set-default-dialog-widget',

        /**
         * @property {String}
         */
        dialogTitle: null,

        /**
         * @property {String}
         */
        dialogOkText: null,

        /**
         * @property {String}
         */
        dialogContentText: null,

        /**
         * @property {String}
         */
        successText: null,

        /**
         * @property {String}
         */
        shoppingListSetDefaultUrl: null,

        /**
         * @property {Object}
         */
        executionData: {},

        /**
         * @inheritDoc
         */
        constructor: function ShoppingListSetDefaultLinkView(options) {
            return ShoppingListSetDefaultLinkView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ShoppingListSetDefaultLinkView.__super__.initialize.call(this, options);
        },

        /**
         * Render dialog widget
         */
        renderDialogWidget: function() {
            const subview = this.subview('popup', new DeleteConfirmationView({
                title: this.dialogTitle,
                okText: this.dialogOkText,
                content: this.dialogContentText,
                className: this.dialogClass
            }));

            subview.on('ok', this._onShoppingSetDefault.bind(this));

            subview.open();
        },

        /**
         * Handle click on link
         *
         * @param {Event} event
         * @private
         */
        _onClick: function(event) {
            event.preventDefault();
            this.renderDialogWidget();
        },

        /**
         * Set as default shopping list event handler
         *
         * @private
         */
        _onShoppingSetDefault: function() {
            $.ajax({
                method: 'PUT',
                url: this.shoppingListSetDefaultUrl,
                success: this._onAjaxSuccess.bind(this)
            });
        },

        /**
         * Show flash message and reload layout blocks on AJAX success
         *
         * @param {Object} response
         * @private
         */
        _onAjaxSuccess: function(response) {
            mediator.trigger('layout-subtree:update:shopping_list_set_default', {
                layoutSubtreeCallback: this._showSuccessFlashMessage.bind(this)
            });
            mediator.trigger('layout-subtree:update:shopping_list_owner');
            mediator.trigger('shopping-list:refresh');
        },

        /**
         * Show success flash message after layout block was updated
         *
         * @private
         */
        _showSuccessFlashMessage: function() {
            mediator.execute('showFlashMessage', 'success', _.escape(this.successText));
        }
    });

    return ShoppingListSetDefaultLinkView;
});
