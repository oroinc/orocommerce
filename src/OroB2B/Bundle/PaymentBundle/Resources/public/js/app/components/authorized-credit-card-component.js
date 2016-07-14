define(function(require) {
    'use strict';

    var AuthorizedCreditCardComponent;
    var $ = require('jquery');
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
            this.options.saveForLaterUse = false;
            AuthorizedCreditCardComponent.__super__.initialize.call(this, options);

            this.$authorizedCard = this.$el.find(this.authorizedOptions.authorizedCard);
            this.$differentCard = this.$el.find(this.authorizedOptions.differentCard);

            this.$el
                .on('click', this.authorizedOptions.authorizedCardHandle, $.proxy(this.showAuthorizedCard, this))
                .on('click', this.authorizedOptions.differentCardHandle, $.proxy(this.showDifferentCard, this));
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

            this.setGlobalPaymentValidate(true);
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

            this.setGlobalPaymentValidate(false);
            this.updateSaveForLater();

            return false;
        },

        onCurrentPaymentMethodSelected: function() {
            this.setGlobalPaymentValidate(this.paymentValidationRequiredComponentState);
            this.updateSaveForLater();
        },

        updateSaveForLater: function() {

            if (this.getGlobalPaymentValidate()) {
                this.setSaveForLaterBasedOnForm();
            } else {
                mediator.trigger('checkout:payment:save-for-later:change', this.options.saveForLaterUse);
            }
        },

        /**
         * @inheritDoc
         */
        beforeTransit: function(eventData) {
            if (!this.getGlobalPaymentValidate()) {
                return;
            }

            AuthorizedCreditCardComponent.__super__.beforeTransit.call(this, eventData);
        },

        /**
         * @inheritDoc
         */
        handleSubmit: function(eventData) {
            if (eventData.responseData.paymentMethod === this.options.paymentMethod &&
                this.paymentValidationRequiredComponentState
            ) {
                AuthorizedCreditCardComponent.__super__.handleSubmit.call(this, eventData);

            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed || !this.disposable) {
                return;
            }

            this.$el
                .off('click', this.authorizedOptions.authorizedCardHandle, $.proxy(this.showAuthorizedCard, this))
                .off('click', this.authorizedOptions.differentCardHandle, $.proxy(this.showDifferentCard, this));

            AuthorizedCreditCardComponent.__super__.dispose.call(this);
        }
    });

    return AuthorizedCreditCardComponent;
});
