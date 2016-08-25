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
        options: {},

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

            this.$button.on('click', _.bind(this.submit, this));
        },

        /**
         * @param {$.Event} e
         */
        submit: function(e) {
            e.preventDefault();
            e.stopPropagation();

            _.each(this.options, _.bind(function(selector, data) {
                if (data === '_sourceElement') {
                    return;
                }

                this.$form.find(selector).val(this.$button.data(data));
            }, this));

            this.$form.submit();
        }
    });

    return QuickAddFormButtonComponent;
});
