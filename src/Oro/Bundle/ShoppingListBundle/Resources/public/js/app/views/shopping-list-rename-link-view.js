define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const actionsTemplate = require('tpl-loader!oroshoppinglist/templates/frontend-dialog/rename-dialog-actions.html');
    const BaseView = require('oroui/js/app/views/base/view');
    const FrontendDialogWidget = require('orofrontend/js/app/components/frontend-dialog-widget');

    const ShoppingListRenameLinkView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'dialogUrl', 'dialogClass', 'dialogTitle', 'labelElement', 'shoppingListUpdateUrl'
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
        dialogUrl: null,

        /**
         * @property {String}
         */
        dialogClass: 'shopping-list-rename-dialog-widget',

        /**
         * @property {String}
         */
        dialogTitle: null,

        /**
         * @property {String}
         */
        contentElement: '.shopping-list-rename-form',

        /**
         * @property {String}
         */
        labelElement: 'input[name="label"]',

        /**
         * @property {String}
         */
        shoppingListUpdateUrl: null,

        /**
         * @inheritDoc
         */
        constructor: function ShoppingListRenameLinkView(options) {
            return ShoppingListRenameLinkView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ShoppingListRenameLinkView.__super__.initialize.call(this, options);
        },

        /**
         * Render dialog widget
         */
        renderDialogWidget: function() {
            this.subview('popup', new FrontendDialogWidget({
                autoRender: true,
                url: this.dialogUrl,
                title: this.dialogTitle,
                contentElement: this.contentElement,
                simpleActionTemplate: false,
                renderActionsFromTemplate: true,
                actionsTemplate: actionsTemplate,
                staticPage: false,
                fullscreenMode: false,
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    autoResize: true,
                    width: 400,
                    dialogClass: this.dialogClass
                }
            }));

            this.listenToOnce(this.subview('popup'), {
                'frontend-dialog:accept': this._onShoppingListRename.bind(this),
                'widgetReady': this._onWidgetReady.bind(this)
            });
        },

        /**
         * Adds event handlers to dialog inner elements.
         * @private
         */
        _onWidgetReady: function() {
            $(this.labelElement).on('keypress', this._onKeyPress.bind(this));
            $(this.labelElement).focus();
        },

        /**
         * Submits rename form when Enter is hit.
         *
         * @param {jQuery.Event} e
         * @private
         */
        _onKeyPress: function(e) {
            if (e.which === 13) {
                this.subview('popup').trigger('frontend-dialog:accept');
                this.subview('popup').remove();
            }
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
         * Change shopping list name event handler
         *
         * @private
         */
        _onShoppingListRename: function() {
            $.ajax({
                method: 'PATCH',
                url: this.shoppingListUpdateUrl,
                data: JSON.stringify({label: $(this.labelElement).val()}),
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
            mediator.trigger('layout-subtree:update:shopping_list_rename', {
                layoutSubtreeCallback: this._showSuccessFlashMessage.bind(this)
            });
            mediator.trigger('shopping-list:refresh');
        },

        /**
         * Show success flash message after layout block was updated
         *
         * @private
         */
        _showSuccessFlashMessage: function() {
            mediator.execute('showFlashMessage', 'success', _.__('oro.frontend.shoppinglist.dialog.rename.success'));
        }
    });

    return ShoppingListRenameLinkView;
});
