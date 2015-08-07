/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var OrderSelectionComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');

    OrderSelectionComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            accountSelect: '.order-order-account-select input[type="hidden"]',
            accountUserSelect: '.order-order-accountuser-select input[type="hidden"]',
            accountRoute: 'orob2b_account_account_user_get_account',
            errorMessage: 'Sorry, unexpected error was occurred'
        },

        /**
         * @property {Object}
         */
        $container: null,

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
            this.$container = options._sourceElement;
            this.loadingMask = new LoadingMaskView({container: this.$container});

            this.$accountSelect = this.$container.find(this.options.accountSelect);
            this.$accountUserSelect = this.$container.find(this.options.accountUserSelect);

            this.$container
                .on('change', this.options.accountSelect, _.bind(this.onAccountChanged, this))
                .on('change', this.options.accountUserSelect, _.bind(this.onAccountUserChanged, this))
            ;
        },

        /**
         * Handle Account change
         *
         * @param {jQuery.Event} e
         */
        onAccountChanged: function(e) {
            this.$accountUserSelect.select2('val', '');
        },

        /**
         * Handle AccountUser change
         *
         * @param {jQuery.Event} e
         */
        onAccountUserChanged: function(e) {
            var accountUserId = this.$accountUserSelect.val();
            if (!accountUserId) {
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
                    if (response.accountId) {
                        self.$accountSelect.select2('val', response.accountId);
                    } else {
                        self.$accountSelect.select2('val', '');
                    }
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

            this.$container.off();

            OrderSelectionComponent.__super__.dispose.call(this);
        }
    });

    return OrderSelectionComponent;
});
