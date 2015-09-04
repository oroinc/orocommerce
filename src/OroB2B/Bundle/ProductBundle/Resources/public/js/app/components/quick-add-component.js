define(function(require) {
    'use strict';

    var QuickAddComponent;
    var _ = require('underscore');
    var $ = require('jquery');
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
        },

        fillComponentData: function(e) {
            var $element = $(e.target);

            var component = $element.data('component-name');
            if (component) {
                this.options._sourceElement.find(this.options.componentSelector).val(component);
            }

            var additional = $element.data('component-additional');
            if (additional) {
                this.options._sourceElement.find(this.options.additionalSelector).val(additional);
            }
        }
    });

    return QuickAddComponent;
});
