import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';
import $ from 'jquery';
import NumberFormatter from 'orolocale/js/formatter/number';
import mediator from 'oroui/js/mediator';
import multiShippingMethodsSelect2Template
    from 'tpl-loader!oroshipping/templates/multi-shipping-methods-select2-template.html';

const GroupShippingMethodsView = BaseView.extend({
    autoRender: true,

    options: {
        template: '',
        selectors: {
            checkoutSummary: '[data-role="checkout-summary"]',
            shippingMethodType: '[data-content="shipping_method_form"] [name^="shippingMethodType"]'
        }
    },

    events: {
        'change select': 'onShippingMethodTypeChange'
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
    },

    /**
     * @inheritdoc
     */
    render: function(options) {
        this.updateShippingMethods(options);
        mediator.trigger('layout:adjustHeight');
        mediator.trigger('checkout:shipping-method:rendered');

        const selectFormat = state => {
            return multiShippingMethodsSelect2Template({
                ...state,
                ...$(state.element).data(),
                formatter: NumberFormatter
            });
        };

        this.$('[data-role="select-shipping-method"]').inputWidget('create', 'select2', {
            initializeOptions: {
                minimumResultsForSearch: -1,
                formatSelection: selectFormat,
                formatResult: selectFormat
            }
        });

        return GroupShippingMethodsView.__super__.render.call(this);
    },

    onShippingMethodTypeChange: function(e) {
        const data = $(e.target).inputWidget('data');
        const methodType = $(data.element);
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

export default GroupShippingMethodsView;
