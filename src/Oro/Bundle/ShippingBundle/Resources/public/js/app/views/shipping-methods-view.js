import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';
import $ from 'jquery';
import NumberFormatter from 'orolocale/js/formatter/number';
import mediator from 'oroui/js/mediator';

const ShippingMethodsView = BaseView.extend({
    autoRender: true,

    options: {
        template: ''
    },

    /**
     * @inheritdoc
     */
    constructor: function ShippingMethodsView(options) {
        ShippingMethodsView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        ShippingMethodsView.__super__.initialize.call(this, options);

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
        const $el = $(this.options.template({
            methods: options || this.options.data.methods,
            currentShippingMethod: this.options.data.currentShippingMethod,
            currentShippingMethodType: this.options.data.currentShippingMethodType,
            formatter: NumberFormatter
        }));

        this.$el.html($el);
    }
});

export default ShippingMethodsView;
