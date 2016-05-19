/** @lends PaymentValidateComponent */
define(function(require) {
    'use strict';

    var PaymentValidateComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    var BaseComponent = require('oroui/js/app/components/base/component');

    PaymentValidateComponent = BaseComponent.extend(/** @exports PaymentValidateComponent.prototype */ {
        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {Object}
         */
        selectors: {
            validateCheckboxSelector: '[name$="[payment_validate]"]'
        },

        /**
         * @property {Boolean}
         */
        defaultState: true,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$el = $(options._sourceElement);
            this.defaultState = this.getCheckboxState();

            mediator.on('checkout:payment:validate:get-value', this.onGetValue, this);
            mediator.on('checkout:payment:validate:change', this.onChange, this);
            mediator.on('checkout:payment:validate:restore-default', this.onRestoreDefault, this);
        },

        /**
         * @param {Boolean} state
         */
        onChange: function(state) {
            this.setCheckboxState(state);
        },

        onRestoreDefault: function() {
            this.setCheckboxState(this.defaultState);
        },

        /**
         * @param {Object} object
         */
        onGetValue: function(object) {
            object.value = this.getCheckboxState();
        },

        /**
         * @param {Boolean} state
         */
        setCheckboxState: function(state) {
            this.getValidateCheckboxElement()
                .prop('checked', state)
                .trigger('change');
        },

        /**
         * @returns {Boolean}
         */
        getCheckboxState: function() {
            return this.getValidateCheckboxElement().prop('checked');
        },

        /**
         * @returns {jQuery}
         */
        getValidateCheckboxElement: function() {
            if (!this.hasOwnProperty('$validateCheckboxElement')) {
                this.$validateCheckboxElement = this.$el.find(this.selectors.validateCheckboxSelector);
            }

            return this.$validateCheckboxElement;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('checkout:payment:validate:get-value', this.onGetValue, this);
            mediator.off('checkout:payment:validate:change', this.onChange, this);
            mediator.off('checkout:payment:validate:restore-default', this.onRestoreDefault, this);

            PaymentValidateComponent.__super__.dispose.call(this);
        }
    });

    return PaymentValidateComponent;
});
