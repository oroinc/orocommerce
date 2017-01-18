define(function(require) {
    'use strict';

    var ShippingMethodsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var mediator = require('oroui/js/mediator');

    ShippingMethodsView = BaseView.extend({
        autoRender: true,

        options: {
            template: ''
        },

        initialize: function(options) {
            ShippingMethodsView.__super__.initialize.apply(this, arguments);

            this.options = _.defaults(options || {}, this.options);
            this.options.template = _.template(this.options.template);

            mediator.on('transition:failed', this.onTransitionFailed, this);
        },

        render: function() {
            this.updateShippingMethods();
            mediator.trigger('layout:adjustHeight');
        },

        updateShippingMethods: function() {
            var $el = $(this.options.template({
                methods: this.options.data.methods,
                currentShippingMethod: this.options.data.currentShippingMethod,
                currentShippingMethodType: this.options.data.currentShippingMethodType,
                formatter: NumberFormatter
            }));

            this.$el.html($el);
        },

        onTransitionFailed: function() {
            this.options.data.methods = [];
            this.render();
        }
    });

    return ShippingMethodsView;
});
