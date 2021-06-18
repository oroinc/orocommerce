define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const QuickAddComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            componentSelector: '[name$="[component]"]',
            additionalSelector: '[name$="[additional]"]',
            componentButtonSelector: '.component-button',
            componentPrefix: 'quick-add'
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @inheritdoc
         */
        constructor: function QuickAddComponent(options) {
            QuickAddComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$form = this.options._sourceElement;

            this.$form.on('click', this.options.componentButtonSelector, _.bind(this.fillComponentData, this));

            mediator.on(this.options.componentPrefix + ':submit', this.submit, this);
        },

        fillComponentData: function(e) {
            const $element = $(e.target);
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
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(this.options.componentPrefix + ':submit', this.submit, this);
            QuickAddComponent.__super__.dispose.call(this);
        }
    });

    return QuickAddComponent;
});
