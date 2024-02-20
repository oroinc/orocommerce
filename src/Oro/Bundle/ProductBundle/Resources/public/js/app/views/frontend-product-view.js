import BaseProductView from 'oroproduct/js/app/views/base-product-view';

const FrontendProductView = BaseProductView.extend({
    elements: Object.assign({}, BaseProductView.prototype.elements, {
        unit: '[name="oro_product_frontend_line_item[unit]"]'
    }),

    /**
     * @inheritdoc
     */
    constructor: function FrontendProductView(options) {
        FrontendProductView.__super__.constructor.call(this, options);
    }
});

export default FrontendProductView;
