/** @lends LateRegistrationView */
define(function(require) {
    'use strict';

    var LateRegistrationView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/views/base/view');

    LateRegistrationView = BaseComponent.extend({

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
         * @inheritDoc
         */
        constructor: function LateRegistrationView() {
            LateRegistrationView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);

            this.$switcher = this.$el.find(this.options.selectors.switcher);
            this.$fieldsContainer = this.$el.find(this.options.selectors.fieldsContainer);
            this.$switcher.on('change', _.bind(this.onOptionChange, this));
            this.onOptionChange();
        },

        onOptionChange: function() {
            var inputs = this.$fieldsContainer.find('input');
            var validationDisabled = false;
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
