/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AccountSelectionComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');

    AccountSelectionComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            accountSelect: '.account-account-select input[type="hidden"]',
            accountUserSelect: '.account-accountuser-select input[type="hidden"]',
            accountRoute: 'orob2b_account_account_user_get_account',
            errorMessage: 'Sorry, unexpected error was occurred'
        },

        /**
         * @property {Object}
         */
        $accountSelect: null,

        /**
         * @property {Object}
         */
        $accountUserSelect: null,

        /**
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = options._sourceElement;
            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.$accountSelect = this.$el.find(this.options.accountSelect);
            this.$accountUserSelect = this.$el.find(this.options.accountUserSelect);

            this.$el
                .on('change', this.options.accountSelect, _.bind(this.onAccountChanged, this))
                .on('change', this.options.accountUserSelect, _.bind(this.onAccountUserChanged, this))
            ;
            this.$accountUserSelect.data('select2_query_additional_params', {'account_id': this.$accountSelect.val()});
        },

        /**
         * Handle Account change
         *
         * @param {jQuery.Event} e
         */
        onAccountChanged: function(e) {
            this.$accountUserSelect.select2('val', '');
            this.$accountUserSelect.data('select2_query_additional_params', {'account_id': this.$accountSelect.val()});

            mediator.trigger('account-account-user:change', {
                accountId: this.$accountSelect.val(),
                accountUserId: this.$accountUserSelect.val()
            });
        },

        /**
         * Handle AccountUser change
         *
         * @param {jQuery.Event} e
         */
        onAccountUserChanged: function(e) {
            var accountUserId = this.$accountUserSelect.val();
            if (!accountUserId) {
                mediator.trigger('account-account-user:change', {
                    accountId: this.$accountSelect.val(),
                    accountUserId: accountUserId
                });
                return;
            }

            var self = this;
            $.ajax({
                url: routing.generate(this.options.accountRoute, {'id': accountUserId}),
                type: 'GET',
                beforeSend: function() {
                    self.loadingMask.show();
                },
                success: function(response) {
                    self.$accountSelect.select2('val', response.accountId || '');
                    self.$accountUserSelect.data('select2_query_additional_params', {'account_id': response.accountId});

                    mediator.trigger('account-account-user:change', {
                        accountId: self.$accountSelect.val(),
                        accountUserId: accountUserId
                    });
                },
                complete: function() {
                    self.loadingMask.hide();
                },
                error: function(xhr) {
                    self.loadingMask.hide();
                    messenger.showErrorMessage(__(self.options.errorMessage), xhr.responseJSON);
                }
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();

            AccountSelectionComponent.__super__.dispose.call(this);
        }
    });

    return AccountSelectionComponent;
});
