define(function(require) {
    'use strict';

    var AuthorizedCreditCardComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var CreditCardComponent = require('orob2bpayment/js/app/components/credit-card-component');

    AuthorizedCreditCardComponent = CreditCardComponent.extend({
        /**
         * @property {Object}
         */
        authorizedOptions: {
            differentCard: '[data-different-card]',
            authorizedCard: '[data-authorized-card]',
            differentCardHandle: '[data-different-card-handle]',
            authorizedCardHandle: '[data-authorized-card-handle]'
        },

        /**
         * @property {Boolean}
         */
        paymentValidationRequiredComponentState: false,

        /**
         * @property {jQuery}
         */
        $authorizedCard: null,

        /**
         * @property {jQuery}
         */
        $differentCard: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            AuthorizedCreditCardComponent.__super__.initialize.call(this, options);

            this.options = _.defaults(options || {}, this.options);

            this.$authorizedCard = this.$el.find(this.authorizedOptions.authorizedCard);
            this.$differentCard = this.$el.find(this.authorizedOptions.differentCard);

            this.$el
                .on('click', this.authorizedOptions.authorizedCardHandle, _.bind(this.showAuthorizedCard, this))
                .on('click', this.authorizedOptions.differentCardHandle, _.bind(this.showDifferentCard, this));
        },

        /**
         * @returns {Boolean}
         */
        showDifferentCard: function() {
            this.$authorizedCard
                .css('position', 'absolute');

            this.$differentCard.show('slide', {direction: 'right'});
            this.$authorizedCard.hide('slide', {direction: 'left'}, (function() {
                this.$authorizedCard.css('position', 'relative');
            }).bind(this));

            this.setPaymentValidateRequired(true);
            this.updateSaveForLater();

            return false;
        },

        /**
         * @returns {Boolean}
         */
        showAuthorizedCard: function() {
            this.$authorizedCard
                .css('position', 'absolute');

            this.$authorizedCard.show('slide', {direction: 'left'});
            this.$differentCard.hide('slide', {direction: 'right'}, (function() {
                this.$authorizedCard.css('position', 'relative');
            }).bind(this));

            this.setPaymentValidateRequired(false);
            this.updateSaveForLater();

            return false;
        },

        onCurrentPaymentMethodSelected: function() {
            this.setPaymentValidateRequired(this.paymentValidationRequiredComponentState);
            this.updateSaveForLater();
        },

        updateSaveForLater: function() {
            if (this.options.currentValidation) {
                if (this.getPaymentValidateRequired()) {
                    this.setSaveForLaterBasedOnForm();
                } else {
                    mediator.trigger('checkout:payment:save-for-later:restore-default');
                }
            } else {
                mediator.trigger('checkout:payment:save-for-later:change', true);
            }
        },

        beforeTransit: function(eventData) {
            if (!this.getPaymentValidateRequired()) {
                return;
            }

            AuthorizedCreditCardComponent.__super__.beforeTransit.call(this, eventData);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el
                .off('click', this.authorizedOptions.authorizedCardHandle, _.bind(this.showAuthorizedCard, this))
                .off('click', this.authorizedOptions.differentCardHandle, _.bind(this.showDifferentCard, this));

            AuthorizedCreditCardComponent.__super__.dispose.call(this);
        }
    });

    return AuthorizedCreditCardComponent;
});
