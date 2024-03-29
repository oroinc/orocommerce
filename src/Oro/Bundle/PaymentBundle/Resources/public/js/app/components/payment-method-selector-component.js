define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const routing = require('routing');

    const PaymentMethodSelectorComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                radio: '[data-choice]',
                itemContainer: '[data-item-container]',
                subform: '[data-form-container]'
            },
            redirectEvent: 'scroll keypress mousedown tap',
            delay: 1000 * 60 * 15 // 15 minutes
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $radios: null,

        /**
         * @property {Boolean}
         */
        disposable: true,

        /**
         * @inheritdoc
         */
        constructor: function PaymentMethodSelectorComponent(options) {
            PaymentMethodSelectorComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.extend(this.options, options);

            this.$el = this.options._sourceElement;

            this.updateForms = this.updateForms.bind(this);

            this.$el.on('change', this.options.selectors.radio, this.updateForms);

            mediator.on('checkout:payment:before-restore-filled-form', this.beforeRestoreFilledForm, this);
            mediator.on('checkout:payment:before-hide-filled-form', this.beforeHideFilledForm, this);
            mediator.on('checkout:payment:remove-filled-form', this.removeFilledForm, this);
            mediator.on('checkout:payment:method:refresh', this.refreshPaymentMethod, this);

            this.$el.on(
                this.options.redirectEvent,
                _.debounce(this.redirectToHomepage.bind(this), this.options.delay)
            );

            this.$el.find(this.options.selectors.radio).filter(':checked').trigger('change');
        },

        redirectToHomepage: function() {
            mediator.execute(
                'redirectTo',
                {url: routing.generate('oro_product_frontend_product_index')}, {redirect: true}
            );
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed || !this.disposable) {
                return;
            }

            this.$el.off();

            mediator.off('checkout:payment:before-restore-filled-form', this.beforeRestoreFilledForm, this);
            mediator.off('checkout:payment:before-hide-filled-form', this.beforeHideFilledForm, this);
            mediator.off('checkout:payment:remove-filled-form', this.removeFilledForm, this);
            mediator.off('checkout:payment:method:refresh', this.refreshPaymentMethod, this);

            PaymentMethodSelectorComponent.__super__.dispose.call(this);
        },

        updateForms: function(e) {
            const $element = $(e.target);
            this.$el.find(this.options.selectors.subform).hide();
            $element.parents(this.options.selectors.itemContainer).find(this.options.selectors.subform).show();
            this.refreshPaymentMethod();
        },

        beforeHideFilledForm: function() {
            this.disposable = false;
        },

        beforeRestoreFilledForm: function() {
            if (this.disposable) {
                this.dispose();
            }
        },

        removeFilledForm: function() {
            // Remove hidden form js component
            if (!this.disposable) {
                this.disposable = true;
                this.dispose();
            }
        },

        refreshPaymentMethod: function() {
            const actualMethod = this.getPaymentMethod();

            mediator.trigger('checkout:payment:method:changed', {paymentMethod: actualMethod});
        },

        getPaymentMethod: function() {
            const $checkedRadio = this.$el.find(this.options.selectors.radio).filter(':checked');
            return $checkedRadio.val();
        }
    });

    return PaymentMethodSelectorComponent;
});
