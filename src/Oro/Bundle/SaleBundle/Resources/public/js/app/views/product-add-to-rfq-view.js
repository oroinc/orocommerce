import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import $ from 'jquery';
import routing from 'routing';
import mediator from 'oroui/js/mediator';
import _ from 'underscore';

const ProductAddToRfqView = BaseView.extend(_.extend({}, ElementsHelper, {
    events: {
        click: 'onClick'
    },

    dropdownWidget: null,

    /**
     * @inheritdoc
     */
    constructor: function ProductAddToRfqView(options) {
        ProductAddToRfqView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        ProductAddToRfqView.__super__.initialize.call(this, options);
        this.deferredInitializeCheck(options, ['productModel', 'dropdownWidget']);
    },

    deferredInitialize: function(options) {
        this.dropdownWidget = options.dropdownWidget;
        if (options.productModel) {
            this.model = options.productModel;
        }
    },

    dispose: function() {
        delete this.dropdownWidget;
        ProductAddToRfqView.__super__.dispose.call(this);
    },

    onClick: function(e) {
        const $button = $(e.currentTarget);
        const productItems = {};

        if (!this.dropdownWidget.validateForm()) {
            return;
        }

        productItems[this.model.get('id')] = [{
            quantity: this.model.get('quantity'),
            unit: this.model.get('unit')
        }];
        const url = routing.generate($button.data('url'), {
            product_items: productItems
        });
        mediator.execute('showLoading');
        mediator.execute('redirectTo', {url: url}, {redirect: true});
    }
}));

export default ProductAddToRfqView;
