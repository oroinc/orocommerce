define(function(require) {
    'use strict';

    var BaseProductView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');

    BaseProductView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            quantity: '[data-name="field__quantity"]',
            unit: '[data-name="field__unit"]'
        },

        modelElements: ['quantity', 'unit'],

        defaults: {
            quantity: 0,
            unit: ''
        },

        initialize: function(options) {
            BaseProductView.__super__.initialize.apply(this, arguments);
            if (!this.model) {
                if (tools.debug) {
                    throw new Error('Model not defined!');
                }
                return;
            }
            this.initializeElements(options);

            $.extend(true, this, _.pick(options, ['defaults']));
            this.setDefaults();
        },

        dispose: function() {
            this.disposeElements();
            BaseProductView.__super__.dispose.apply(this, arguments);
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
