define(function(require) {
    'use strict';

    var PaymentMethodSelectorComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var routing = require('routing');

    PaymentMethodSelectorComponent = BaseComponent.extend({
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
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend(this.options, options);

            this.$el = this.options._sourceElement;
            this.$el.on('change', this.options.selectors.radio, _.bind(this.updateForms, this));

            mediator.on('checkout:payment:method:get-value', _.bind(this.onGetValue, this));

            this.$el.on(
                this.options.redirectEvent,
                _.debounce(_.bind(this.redirectToHomepage, this), this.options.delay)
            );

            mediator.on('checkout:payment:before-restore-filled-form', _.bind(this.beforeRestoreFilledForm, this));
            mediator.on('checkout:payment:before-hide-filled-form', _.bind(this.beforeHideFilledForm, this));
        },

        redirectToHomepage: function() {
            mediator.execute(
                'redirectTo',
                {url: routing.generate('orob2b_product_frontend_product_index')}, {redirect: true}
            );
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed || !this.disposable) {
                return;
            }

            this.$el.off();
            mediator.off('checkout:payment:before-restore-filled-form', _.bind(this.beforeRestoreFilledForm, this));
            mediator.off('checkout:payment:before-hide-filled-form', _.bind(this.beforeHideFilledForm, this));

            mediator.off('checkout:payment:method:get-value', _.bind(this.onGetValue, this));

            PaymentMethodSelectorComponent.__super__.dispose.call(this);
        },

        updateForms: function(e) {
            var $element =  $(e.target);
            this.$el.find(this.options.selectors.subform).hide();
            $element.parents(this.options.selectors.itemContainer).find(this.options.selectors.subform).show();
            mediator.trigger('checkout:payment:method:changed', {paymentMethod: $element.val()});
        },

        beforeHideFilledForm: function($filledForm) {
            if ($filledForm.is(this.$el)) {
                this.disposable = false;
            }
        },

        beforeRestoreFilledForm: function($filledForm) {
            if (!$filledForm.is(this.$el)) {
                this.dispose();
            }
        },

        /**
         * @param {Object} object
         */
        onGetValue: function(object) {
            var $checkedRadio = this.$el.find(this.options.selectors.radio).filter(':checked');
            object.value = $checkedRadio.val();
        }
    });

    return PaymentMethodSelectorComponent;
});
