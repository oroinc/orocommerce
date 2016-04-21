define(function(require) {
    'use strict';

    var BaseProductView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');

    BaseProductView = BaseView.extend(_.extend({}, ElementsHelper, {
        options: {
            elements: {
                quantity: '[data-role="field-quantity"]',
                unit: '[data-role="field-unit"]'
            }
        },

        defaults: {
            quantity: 0,
            unit: ''
        },

        initialize: function(options) {
            var self = this;
            BaseProductView.__super__.initialize.apply(this, arguments);
            if (!this.model) {
                if (tools.debug) {
                    throw new Error('Model not defined!');
                }
                return;
            }
            $.extend(true, this, _.pick(options, ['defaults']));

            this.setDefaults();
            this.initializeElements(options);

            this.getElement('quantity').change(function() {
                self.model.set('quantity', this.value);
            }).change();

            this.getElement('unit').change(function() {
                self.model.set('unit', this.value);
            }).change();
        },

        setDefaults: function() {
            var model = this.model;
            _.each(this.defaults, function(value, attribute) {
                if (!model.has(attribute)) {
                    model.set(attribute, value);
                }
            });
        }
    }));

    return BaseProductView;
});
