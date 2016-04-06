define(function(require) {
    'use strict';

    var PaymentMethodSelectorComponent;
    var _ = require('underscore');
    var $ = require('jquery');
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
                subform: '[data-form-container]'
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
            if (this.$radios.length) {
                this.updateForms();
                this.$el.on('change', this.options.selectors.radio, _.bind(this.updateForms, this));
            }

            $(document).on('scroll keypress mousedown tap' , _.debounce(function(){window.location=routing.generate('orob2b_product_frontend_product_index')}, 1000*60*15));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.$radios.length) {
                this.$el.off('change', this.options.selectors.radio, _.bind(this.updateForms, this));
            }

            PaymentMethodSelectorComponent.__super__.dispose.call(this);
        },

        updateForms: function() {
            var $selected = this.$radios.filter(':checked');
            _.each(
                this.$radios,
                function(item) {
                    var $item = $(item);
                    if ($item.data('choice') == $selected.data('choice')) {
                        $item.closest(this.options.selectors.item_container).find(this.options.selectors.subform).show();
                    } else {
                        $item.closest(this.options.selectors.item_container).find(this.options.selectors.subform).hide();
                    }
                },
                this
            );
        }
    });

    return PaymentMethodSelectorComponent;
});
