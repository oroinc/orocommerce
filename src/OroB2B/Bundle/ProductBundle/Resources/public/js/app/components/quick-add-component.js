define(function(require) {
    'use strict';

    var QuickAddComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    QuickAddComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            'componentSelector': '[name$="[component]"]',
            'additionalSelector': '[name$="[additional]"]',
            'componentButtonSelector': '.component-button'
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$form = this.options._sourceElement;

            this.$form.on('click', this.options.componentButtonSelector, _.bind(this.fillComponentData, this));

            mediator.on('quick-add:submit', this.submit, this);
        },

        fillComponentData: function(e) {
            var $element = $(e.target);
            this.submit($element.data('component-name'), $element.data('component-additional'));
        },

        /**
         * @param {String} component
         * @param {String} additional
         */
        submit: function(component, additional) {
            this.$form.find(this.options.componentSelector).val(component);
            this.$form.find(this.options.additionalSelector).val(additional);
            this.$form.submit();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('quick-add:submit', this.submit, this);
            QuickAddComponent.__super__.dispose.call(this);
        }
    });

    return QuickAddComponent;
});
