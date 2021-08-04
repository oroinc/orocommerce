/** @lends LateRegistrationView */
define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/views/base/view');

    const LateRegistrationView = BaseComponent.extend({

        /**
         * @property {jQuery}
         */
        $el: null,

        options: {
            selectors: {
                switcher: null,
                fieldsContainer: null
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function LateRegistrationView(options) {
            LateRegistrationView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);

            this.$switcher = this.$el.find(this.options.selectors.switcher);
            this.$fieldsContainer = this.$el.find(this.options.selectors.fieldsContainer);
            this.$switcher.on('change', this.onOptionChange.bind(this));
            this.onOptionChange();
        },

        onOptionChange: function() {
            const inputs = this.$fieldsContainer.find('input');
            let validationDisabled = false;
            if ($(this.$switcher).is(':checked')) {
                $(this.$fieldsContainer).show();
            } else {
                $(this.$fieldsContainer).hide();
                validationDisabled = true;
            }
            _.each(inputs, function(input) {
                $(input).prop('disabled', validationDisabled).inputWidget('refresh');
            });
        }
    });

    return LateRegistrationView;
});
