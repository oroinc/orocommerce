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

            mediator.on('transition:failed', this.render.bind(this, []));
        },

        render: function(options) {
            this.updateShippingMethods(options);
            mediator.trigger('layout:adjustHeight');
            mediator.trigger('checkout:shipping-method:rendered');
        },

        updateShippingMethods: function(options) {
            var $el = $(this.options.template({
                methods: options || this.options.data.methods,
                currentShippingMethod: this.options.data.currentShippingMethod,
                currentShippingMethodType: this.options.data.currentShippingMethodType,
                formatter: NumberFormatter
            }));
            this.$el.html($el);
        }
    });

    return ShippingMethodsView;
});
