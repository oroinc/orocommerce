/*global define*/
define(function(require) {
    'use strict';

    var QuickAddFormButtonComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    QuickAddFormButtonComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            submitWithErrors: false
        },

        /**
         * @property {jQuery}
         */
        $button: null,

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$button = this.options._sourceElement;
            this.$form = this.$button.closest('form');

            if (this.formHasErrors() && !this.options.submitWithErrors) {
                this.$button.addClass('disabled');
            }

            this.$button.on('click', _.bind(this.submit, this));
        },

        /**
         * @param {$.Event} e
         */
        submit: function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (this.formHasErrors() && !this.options.submitWithErrors) {
                return;
            }

            _.each(this.options, _.bind(function(selector, data) {
                if (data === '_sourceElement') {
                    return;
                }

                this.$form.find(selector).val(this.$button.data(data));
            }, this));

            this.$form.submit();
        },

        formHasErrors: function() {
            return this.$form.closest('.validation-info').find('.import-errors').length;
        }
    });

    return QuickAddFormButtonComponent;
});
