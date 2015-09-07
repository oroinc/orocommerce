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
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('click', this.options.componentButtonSelector, _.bind(this.fillComponentData, this));

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
            this.options._sourceElement.find(this.options.componentSelector).val(component);
            this.options._sourceElement.find(this.options.additionalSelector).val(additional);
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
