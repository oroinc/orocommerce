define(function(require) {
    'use strict';

    var PaymentMethodSelectorComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

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
            }
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

            this.$radios.on('change', _.bind(this.updateForms, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

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
