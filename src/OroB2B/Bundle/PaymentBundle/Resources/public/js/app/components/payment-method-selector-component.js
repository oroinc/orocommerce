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
                radio: '[data-selector-role="radio"]',
                item_container: '[data-selector-role="item-container"]',
                subform: '[data-selector-role="form-container"]'
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;

            this.updateForms();
            this.$el.find(this.options.selectors.radio).on('change', _.bind(this.updateForms, this));
        },

        updateForms: function() {
            var $radios = this.$el.find(this.options.selectors.radio);
            var $selected = $radios.filter(':checked');
            console.log('event', $radios, $selected);
            _.each(
                $radios,
                function(item) {
                    var $item = $(item);
                    if ($item.data('selector-id') == $selected.data('selector-id')) {
                        $item.closest(this.options.selectors.item_container).find(this.options.selectors.subform).show();
                        console.log('show');
                    } else {
                        $item.closest(this.options.selectors.item_container).find(this.options.selectors.subform).hide();
                        console.log('hide', $item, $selected);
                    }
                },
                this
            );
        }
    });

    return PaymentMethodSelectorComponent;
});
