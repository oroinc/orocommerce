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
            accountUserCollection: '.account-accountuser-collection select',
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
         * @property {Object}
         */
        $accountUserCollection: null,

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
            this.$accountUserCollection = this.$el.find(this.options.accountUserCollection);

            this.$el
                .on('change', this.options.accountSelect, _.bind(this.onAccountChanged, this))
                .on('change', this.options.accountUserSelect, _.bind(this.onAccountUserChanged, this))
                .on('change', this.options.accountUserCollection, _.bind(this.onAccountUserCollectionChanged, this))
            ;
            this.$accountUserSelect.data('select2_query_additional_params', {'account_id': this.$accountSelect.val()});

            this.updateAccountUserCollectionChoices(true);
        },

        /**
         * Handle Account change
         *
         * @param {jQuery.Event} e
         */
        onAccountChanged: function(e) {
            this.$accountUserSelect.select2('val', '');
            this.$accountUserSelect.data('select2_query_additional_params', {'account_id': this.$accountSelect.val()});

            this.updateAccountUserCollectionChoices();

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
                    self.updateAccountUserCollectionChoices();

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

        /**
         * Handle AccountUserCollection change
         *
         * @param {jQuery.Event} e
         */
        onAccountUserCollectionChanged: function(e) {
            var firstVal = _.first(this.$accountUserCollection.val());

            if (!this.$accountSelect.select2('val') && firstVal) {
                var items = this.$accountUserCollection.data('items');

                this.$accountSelect.select2('val', items[firstVal].account);
                this.$accountSelect.trigger('change');
            }
        },

        /**
         * @param {Boolean} init
         */
        updateAccountUserCollectionChoices: function (init) {
            var accountId = Number(this.$accountSelect.val());
            var collection = this.$accountUserCollection;
            var collectionItems = collection.data('items');

            if (init || false) {
                _.each(this.$accountUserCollection.find('option'), function(option) {
                    var item = collectionItems[$(option).val()];
                    if (accountId && item.account !== accountId) {
                        $(option).remove();
                    }
                });
            } else {
                var collectionVal = accountId ? collection.val() : null;

                collection.empty();
                _.each(collectionItems, function(item) {
                    if (!accountId || item.account === accountId) {
                        var option = $('<option>')
                            .html(item.label)
                            .val(item.value)
                            .attr('account', item.account)
                            .appendTo(collection);

                        if (_.contains(collectionVal, item.value)) {
                            option.prop('selected', true);
                        }
                    }
                });
                collection.trigger('change');
            }
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
