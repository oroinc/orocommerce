define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const CreditCardComponent = require('oropaypal/js/app/components/credit-card-component');

    const AuthorizedCreditCardComponent = CreditCardComponent.extend({
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
         * @inheritdoc
         */
        constructor: function AuthorizedCreditCardComponent(options) {
            AuthorizedCreditCardComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options.saveForLaterUse = false;
            AuthorizedCreditCardComponent.__super__.initialize.call(this, options);

            this.$authorizedCard = this.$el.find(this.authorizedOptions.authorizedCard);
            this.$differentCard = this.$el.find(this.authorizedOptions.differentCard);

            this.showAuthorizedCard = this.showAuthorizedCard.bind(this);
            this.showDifferentCard = this.showDifferentCard.bind(this);

            this.$el
                .on('click', this.authorizedOptions.authorizedCardHandle, this.showAuthorizedCard)
                .on('click', this.authorizedOptions.differentCardHandle, this.showDifferentCard);
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
         * @inheritdoc
         */
        beforeTransit: function(eventData) {
            if (!this.getGlobalPaymentValidate()) {
                return;
            }

            AuthorizedCreditCardComponent.__super__.beforeTransit.call(this, eventData);
        },

        /**
         * @inheritdoc
         */
        handleSubmit: function(eventData) {
            if (eventData.responseData.paymentMethod === this.options.paymentMethod &&
                this.paymentValidationRequiredComponentState
            ) {
                AuthorizedCreditCardComponent.__super__.handleSubmit.call(this, eventData);
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed || !this.disposable) {
                return;
            }

            this.$el
                .off('click', this.authorizedOptions.authorizedCardHandle, this.showAuthorizedCard)
                .off('click', this.authorizedOptions.differentCardHandle, this.showDifferentCard);

            AuthorizedCreditCardComponent.__super__.dispose.call(this);
        }
    });

    return AuthorizedCreditCardComponent;
});
