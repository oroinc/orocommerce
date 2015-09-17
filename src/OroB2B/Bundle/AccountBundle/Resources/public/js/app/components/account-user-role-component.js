/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AccountUserRole;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');

    AccountUserRole = BaseComponent.extend({
        /**
         * @property {Object}
         */
        targetElement: null,

        /**
         * @property {Object}
         */
        appendElement: null,

        /**
         * @property {Object}
         */
        removeElement: null,

        /**
         * @property {String}
         */
        datagridName: null,

        /**
         * Account change confirmation dialog is shown
         *
         * @property {Boolean}
         */
        accountChangeConfirmationShown: false,

        /**
         * @property {integer}
         */
        originalValue: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.targetElement = $('#' + options.accountFieldId);
            this.appendElement = $('#' + options.appendFieldId);
            this.removeElement = $('#' + options.removeFieldId);
            this.datagridName = options.datagridName;
            var previousValue = this.originalValue = this.targetElement.val();
            var self = this;

            this.targetElement.on('change', function() {
                var value = $(this).val();

                if (value === previousValue) {
                    return;
                }

                var showRoles = !value || self.originalValue === value;

                if (!self.accountChangeConfirmationShown && !showRoles) {
                    setTimeout(function() {
                        self.targetElement.val(previousValue).trigger('change');
                    }, 10);
                } else {
                    previousValue = value;
                }

                self._changeAccountAction(value, showRoles);
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.targetElement.off('change');

            if (this.changeAccountConfirmDialog) {
                this.changeAccountConfirmDialog.dispose();
                delete this.changeAccountConfirmDialog;
            }

            AccountUserRole.__super__.dispose.call(this);
        },

        /**
         * Change account action
         *
         * @param {String} value
         * @param {Boolean} showRoles
         * @private
         */
        _changeAccountAction: function(value, showRoles) {

            if (this.accountChangeConfirmationShown || showRoles) {
                if (value) {
                    this.targetElement.val(value).trigger('change');
                }
                this._updateAccountUserGrid(value, showRoles);
            } else {
                this._getAccountConfirmDialog(function() {
                    this.accountChangeConfirmationShown = true;
                    this.targetElement.val(value).trigger('change');
                });
            }
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
                this.changeAccountConfirmDialog.on('ok', _.bind(okCallback, this));
            }
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
         * @param {Boolean} showRoles
         * @private
         */
        _updateAccountUserGrid: function(value, showRoles) {
            if (value) {
                mediator.trigger('datagrid:setParam:' + this.datagridName, 'newAccount', value);
            } else {
                mediator.trigger('datagrid:removeParam:' + this.datagridName, 'newAccount');
            }

            // Add param to know this request is change account action
            mediator.trigger('datagrid:setParam:' + this.datagridName, 'changeAccountAction', 1);

            // Show current roles when current and original has same values or account removed
            if (showRoles) {
                mediator.trigger('datagrid:removeParam:' + this.datagridName, 'hideHasRole');
            } else {
                mediator.trigger('datagrid:setParam:' + this.datagridName, 'hideHasRole', 1);
            }

            mediator.trigger('datagrid:removeParam:' + this.datagridName, 'data_in');
            mediator.trigger('datagrid:removeParam:' + this.datagridName, 'data_not_in');

            this.appendElement.val(null);
            this.removeElement.val(null);

            mediator.trigger('datagrid:doReset:' + this.datagridName);
        }
    });

    return AccountUserRole;
});
