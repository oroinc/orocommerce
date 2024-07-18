define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const $ = require('jquery');
    const NumberFormatter = require('orolocale/js/formatter/number');
    const mediator = require('oroui/js/mediator');

    const GroupShippingMethodsView = BaseView.extend({
        autoRender: true,

        options: {
            template: '',
            selectors: {
                checkoutSummary: '[data-role="checkout-summary"]',
                shippingMethodType: '[data-content="shipping_method_form"] [name^="shippingMethodType"]'
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function GroupShippingMethodsView(options) {
            GroupShippingMethodsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            GroupShippingMethodsView.__super__.initialize.call(this, options);

            this.options = _.defaults(options || {}, this.options);
            this.options.template = _.template(this.options.template);

            mediator.on('transition:failed', this.render.bind(this, []));
            mediator.on('checkout:shipping-method:rendered', this.onShippingMethodRendered, this);
        },

        /**
         * @inheritdoc
         */
        render: function(options) {
            this.updateShippingMethods(options);
            mediator.trigger('layout:adjustHeight');
            mediator.trigger('checkout:shipping-method:rendered');
        },

        onShippingMethodRendered: function() {
            $(this.options.selectors.checkoutSummary).on(
                'change',
                this.options.selectors.shippingMethodType,
                this.onShippingMethodTypeChange.bind(this)
            );
        },

        onShippingMethodTypeChange: function(e) {
            const methodType = $(e.target);
            const method = methodType.data('shipping-method');
            const type = methodType.data('shipping-type');
            const itemId = methodType.data('item-id');
            mediator.trigger('group-multi-shipping-method:changed', itemId, method, type);
        },

        updateShippingMethods: function(options) {
            const $el = $(this.options.template({
                groupId: options || this.options.data.groupId,
                methods: options || this.options.data.methods,
                currentShippingMethod: this.options.data.currentShippingMethod,
                currentShippingMethodType: this.options.data.currentShippingMethodType,
                formatter: NumberFormatter
            }));

            this.$el.html($el);
        }
    });

    return GroupShippingMethodsView;
});
