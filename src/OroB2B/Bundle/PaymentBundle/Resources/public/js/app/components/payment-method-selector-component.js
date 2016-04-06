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
                item_container: '[data-item-container]',
                subform: '[data-form-container]',
                submit_button: '[data-payment-method-submit]',
                no_methods: 'payment-no-methods'
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
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend(this.options, options);

            this.$el = this.options._sourceElement;
            this.$radios = this.$el.find(this.options.selectors.radio);
            this.$el.on('change', this.options.selectors.radio, _.bind(this.updateForms, this));

            this.$el.on(
                this.options.redirectEvent,
                _.debounce(_.bind(this.redirectToHomepage, this), this.options.delay)
            );
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
            if (this.disposed) {
                return;
            }

            this.$el.off();

            PaymentMethodSelectorComponent.__super__.dispose.call(this);
        },

        updateForms: function(e) {
            var element =  e.target;
            this.$el.find(this.options.selectors.subform).hide();
            $(element).parents(this.options.selectors.item_container).find(this.options.selectors.subform).show();
        }
    });

    return PaymentMethodSelectorComponent;
});
