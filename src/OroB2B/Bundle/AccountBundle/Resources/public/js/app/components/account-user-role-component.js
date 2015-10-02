/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AccountUserRole;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');

    AccountUserRole = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            accountFieldId: '#accountFieldId',
            datagridName: 'account-users-datagrid',
            originalValue: null
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('change', this.options.accountFieldId, _.bind(this.onAccountSelectorChange, this));
        },

        onAccountSelectorChange: function(e) {
            var value = e.target.value;

            if (!value) {
                this._updateAccountUserGrid(value);
            }

            if (value !== this.options.originalValue) {
                this._getAccountConfirmDialog(function() {
                    this._updateAccountUserGrid(value);
                });
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off('change');

            if (this.changeAccountConfirmDialog) {
                this.changeAccountConfirmDialog.off();
                this.changeAccountConfirmDialog.dispose();
                delete this.changeAccountConfirmDialog;
            }

            AccountUserRole.__super__.dispose.call(this);
        },

        /**
         * Show account confirmation dialog
         *
         * @param {function()} okCallback
         * @private
         */
        _getAccountConfirmDialog: function(okCallback) {
            if (!this.changeAccountConfirmDialog) {
                this.changeAccountConfirmDialog = this._createChangeAccountConfirmationDialog();
            }

            this.changeAccountConfirmDialog.off('ok').on('ok', _.bind(okCallback, this));
            this.changeAccountConfirmDialog.open();
        },

        /**
         * Create change account confirmation dialog
         *
         * @returns {Modal}
         * @private
         */
        _createChangeAccountConfirmationDialog: function() {
            return new Modal({
                title: __('orob2b.account.account_user_role.change_account_confirmation_title'),
                okText: __('orob2b.account.account_user_role.continue'),
                cancelText: __('orob2b.account.account_user_role.cancel'),
                content: __('orob2b.account.account_user_role.content'),
                className: 'modal modal-primary',
                okButtonClass: 'btn-primary btn-large'
            });
        },

        /**
         * Update account user grid
         *
         * @param {String} value
         * @private
         */
        _updateAccountUserGrid: function(value) {
            if (value) {
                mediator.trigger('datagrid:setParam:' + this.options.datagridName, 'newAccount', value);
            } else {
                mediator.trigger('datagrid:removeParam:' + this.options.datagridName, 'newAccount');
            }

            // Add param to know this request is change account action
            mediator.trigger('datagrid:setParam:' + this.options.datagridName, 'changeAccountAction', 1);
            mediator.trigger('datagrid:doReset:' + this.options.datagridName);
        }
    });

    return AccountUserRole;
});
