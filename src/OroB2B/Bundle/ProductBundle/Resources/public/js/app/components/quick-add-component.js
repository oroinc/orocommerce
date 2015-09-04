/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var QuickAddComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    QuickAddComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            'componentSelector': '[name$="[component]"]',
            'additionalDataSelector': '[name$="[additional]"]',
            'componentButtonSelector': '.component-button'
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('click', this.options.componentButtonSelector, _.bind(this.fillComponentData, this));
        },

        fillComponentData: function (e) {
            var $element = $(e.target);

            var component = $element.data('component-name');
            if (component) {
                this.options._sourceElement.find(this.options.componentSelector).val(component);
            }

            var additionalData = $element.data('component-additional-data');
            if (additionalData) {
                this.options._sourceElement.find(this.options.additionalDataSelector).val(additionalData);
            }
        }
    });

    return QuickAddComponent;
});
