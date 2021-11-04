define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const actionsTemplate = require('tpl-loader!oroshoppinglist/templates/frontend-dialog/assign-dialog-actions.html');
    const BaseView = require('oroui/js/app/views/base/view');
    const FrontendDialogWidget = require('orofrontend/js/app/components/frontend-dialog-widget');

    const ShoppingListAssignLinkView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'dialogUrl', 'dialogClass', 'dialogTitle', 'contentElement',
            'ownerElement', 'shoppingListOwnerChangeUrl'
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
        dialogClass: 'shopping-list-assign-dialog-widget',

        /**
         * @property {String}
         */
        dialogTitle: null,

        /**
         * @property {String}
         */
        contentElement: '.shopping-list-assign-grid',

        /**
         * @property {String}
         */
        ownerElement: 'input[name="assigned"]:checked',

        /**
         * @property {String}
         */
        shoppingListOwnerChangeUrl: null,

        /**
         * @inheritdoc
         */
        constructor: function ShoppingListAssignLinkView(options) {
            return ShoppingListAssignLinkView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ShoppingListAssignLinkView.__super__.initialize.call(this, options);
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
                    width: 800,
                    dialogClass: this.dialogClass
                }
            }));

            this.listenToOnce(this.subview('popup'), {
                'frontend-dialog:accept': this._onShoppingListOwnerChange.bind(this)
            });
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
         * Change shopping list owner event handler
         *
         * @private
         */
        _onShoppingListOwnerChange: function() {
            const ownerId = $(this.ownerElement).val();
            $.ajax({
                method: 'PUT',
                url: this.shoppingListOwnerChangeUrl,
                data: {
                    ownerId: ownerId
                },
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
            mediator.execute('showFlashMessage', 'success', _.escape(response));
            mediator.trigger('layout-subtree:update:shopping_list_owner', {
                layoutSubtreeFailCallback: this._checkPermissions.bind(this)
            });
            mediator.trigger('shopping-list:refresh');
        },

        /**
         * Check permissions to the shopping list after layout block was updated
         *
         * @param {Object} jqxhr
         * @private
         */
        _checkPermissions: function(jqxhr) {
            if (jqxhr.status === 403) {
                window.location.reload();
            }
        }
    });

    return ShoppingListAssignLinkView;
});
