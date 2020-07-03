define(function(require) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const DeleteConfirmationView = require('oroui/js/delete-confirmation');

    const ShoppingListDeleteLinkView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'dialogClass', 'dialogTitle', 'dialogOkText', 'dialogContentText',
            'shoppingListDeleteUrl', 'executionData'
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
        dialogClass: 'modal shopping-list-delete-dialog-widget',

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
        shoppingListDeleteUrl: null,

        /**
         * @property {Object}
         */
        executionData: {},

        /**
         * @inheritDoc
         */
        constructor: function ShoppingListDeleteLinkView(options) {
            return ShoppingListDeleteLinkView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ShoppingListDeleteLinkView.__super__.initialize.call(this, options);
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

            subview.on('ok', this._onShoppingDelete.bind(this));

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
         * Delete shopping list event handler
         *
         * @private
         */
        _onShoppingDelete: function() {
            $.ajax({
                method: 'POST',
                url: this.shoppingListDeleteUrl,
                data: this.executionData,
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
            mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
        }
    });

    return ShoppingListDeleteLinkView;
});
