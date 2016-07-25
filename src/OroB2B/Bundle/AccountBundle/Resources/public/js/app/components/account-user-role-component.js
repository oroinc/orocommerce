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
            originalValue: null,
            previousValueDataAttribute: 'previousValue'
        },

        /**
         * @property {jQuery.Element}
         */
        accountField: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.accountField = this.options._sourceElement.find(this.options.accountFieldId);
            this.accountField.data(this.options.previousValueDataAttribute, this.options.originalValue);

            this.options._sourceElement
                .on('change', this.options.accountFieldId, _.bind(this.onAccountSelectorChange, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onAccountSelectorChange: function(e) {
            var value = e.target.value;

            if (value === this.options.originalValue) {
                this._updateGridAndSaveParameters(value);

                return;
            }

            this._getAccountConfirmDialog(
                function() {
                    this._updateGridAndSaveParameters(value);
                },
                function() {
                    this.accountField
                        .inputWidget('val', this.accountField.data(this.options.previousValueDataAttribute));
                    this.accountField.data(this.options.previousValueDataAttribute, this.options.originalValue);
                }
            );
        },

        /**
         * @param {String} value
         * @private
         */
        _updateGridAndSaveParameters: function(value) {
            this._updateAccountUserGrid(value);
            this.accountField.data(this.options.previousValueDataAttribute, value);
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
         * @param {function()} cancelCallback
         * @private
         */
        _getAccountConfirmDialog: function(okCallback, cancelCallback) {
            if (!this.changeAccountConfirmDialog) {
                this.changeAccountConfirmDialog = this._createChangeAccountConfirmationDialog();
            }

            this.changeAccountConfirmDialog
                .off('ok').on('ok', _.bind(okCallback, this))
                .off('cancel').on('cancel', _.bind(cancelCallback, this));

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
