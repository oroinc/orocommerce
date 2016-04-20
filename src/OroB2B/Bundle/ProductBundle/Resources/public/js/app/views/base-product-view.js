define(function(require) {
    'use strict';

    var BaseProductView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var _ = require('underscore');

    BaseProductView = BaseView.extend({
        defaults: {
            qty: 0
        },

        initialize: function(options) {
            BaseProductView.__super__.initialize.apply(this, arguments);
            if (!this.model) {
                return;
            }

            $.extend(true, this, _.pick(options, ['defaults']));
            this.setDefaults();
        },

        setDefaults: function() {
            var model = this.model;
            _.each(this.defaults, function(value, attribute) {
                if (!model.has(attribute)) {
                    model.set(attribute, value);
                }
            });
        }
    });

    return BaseProductView;
});
