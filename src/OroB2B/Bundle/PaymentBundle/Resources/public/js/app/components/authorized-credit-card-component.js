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
            selectors: {
                differentCardHandle: '[data-different-card-handle]'
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property bool
         */
        isAuthorizedCard: false,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;
            this.isAuthorizedCard = (this.options.authorizedCard !== null);

            this.$el
                .on('click', this.options.selectors.differentCardHandle, _.bind(this.showDifferentCard, this));

            mediator.on('checkout:payment:credit-card-authorized', _.bind(this.showAuthorizedCard, this));
        },

        showDifferentCard: function() {
            this.$el.hide("slide", { direction: "left" });
            this.isAuthorizedCard = false;
            mediator.trigger('checkout:payment:credit-card-different');

            return false;
        },

        showAuthorizedCard: function() {
            this.$el.show("slide", { direction: "left" });
            this.isAuthorizedCard = true;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el
                .off('click', this.options.selectors.differentCardHandle, _.bind(this.showDifferentCard, this));

            mediator.off('checkout:payment:credit-card-authorized', _.bind(this.showAuthorizedCard, this));

            AuthorizedCreditCardComponent.__super__.dispose.call(this);
        }
    });

    return AuthorizedCreditCardComponent;
});
