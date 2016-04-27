define(function(require) {
    'use strict';

    var AuthorizedCreditCardComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    AuthorizedCreditCardComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            authorizedCard: null,
            paymentMethod: null,
            selectors: {
                differentCard: '[data-different-card]',
                authorizedCard: '[data-authorized-card]',
                differentCardHandle: '[data-different-card-handle]',
                authorizedCardHandle: '[data-authorized-card-handle]',
                paymentValidate: '[name$="[payment_validate]"]'
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $authorizedCard: null,

        /**
         * @property {jQuery}
         */
        $differentCard: null,

        /**
         * @property {jQuery}
         */
        $paymentValidateElement: null,

        /**
         * @property {Boolean}
         */
        lastValidationComponentState: false,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;
            this.$authorizedCard = this.$el.find(this.options.selectors.authorizedCard);
            this.$differentCard = this.$el.find(this.options.selectors.differentCard);
            this.changePaymentValidateState(!this.options.authorizedCard);

            mediator.on('checkout:payment:method:changed', this.onMethodChanged, this);

            this.$el
                .on('click', this.options.selectors.authorizedCardHandle, _.bind(this.showAuthorizedCard, this))
                .on('click', this.options.selectors.differentCardHandle, _.bind(this.showDifferentCard, this));
        },

        showDifferentCard: function() {
            this.$authorizedCard
                .css('width', this.$authorizedCard.outerWidth() + 'px')
                .css('position', 'absolute');
            this.$el.effect('size', {to: {height: this.$differentCard.outerHeight()}, scale: 'box'}, 100, (function() {
                this.$authorizedCard.hide('slide', {direction: 'left'}, (function() {
                    this.$authorizedCard.css('position', 'relative');
                }).bind(this));
                this.$differentCard.show('slide', {direction: 'right'});
            }).bind(this));

            this.changePaymentValidateState(true);

            return false;
        },

        showAuthorizedCard: function() {
            this.$authorizedCard.css('position', 'absolute');
            this.$authorizedCard.show('slide', {direction: 'left'}, (function() {
                this.$el
                    .effect('size', {to: {height: this.$authorizedCard.outerHeight()}, scale: 'box'}, 100);
            }).bind(this));
            this.$differentCard.hide('slide', {direction: 'right'}, (function() {
                this.$authorizedCard.css('position', 'relative');
            }).bind(this));

            this.changePaymentValidateState(false);

            return false;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentValidate: function() {
            if (!this.hasOwnProperty('$paymentValidateElement')) {
                this.$paymentValidateElement = $(this.options.selectors.paymentValidate);
            }

            return this.$paymentValidateElement;
        },

        /**
         * @param {Boolean} state
         */
        changePaymentValidateState: function(state) {
            this.lastValidationComponentState = state;
            this.getPaymentValidate().prop('checked', state);
        },

        onMethodChanged: function(eventData) {
            if (eventData.paymentMethod === this.options.paymentMethod) {
                this.changePaymentValidateState(this.lastValidationComponentState);
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('checkout:payment:method:changed', this.onMethodChanged, this);

            this.$el
                .off('click', this.options.selectors.authorizedCardHandle, _.bind(this.showAuthorizedCard, this))
                .off('click', this.options.selectors.differentCardHandle, _.bind(this.showDifferentCard, this));

            AuthorizedCreditCardComponent.__super__.dispose.call(this);
        }
    });

    return AuthorizedCreditCardComponent;
});
