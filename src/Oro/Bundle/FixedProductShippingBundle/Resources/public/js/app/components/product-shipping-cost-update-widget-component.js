import $ from 'jquery';
import _ from 'underscore';
import routing from 'routing';
import widgetManager from 'oroui/js/widget-manager';
import BaseComponent from 'oroui/js/app/components/base/component';

const ProductShippingCostUpdateWidget = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        widgetAlias: null,
        unitFormId: null,
        precisionFormId: null
    },

    /**
     * @inheritdoc
     */
    constructor: function ProductShippingCostUpdateWidget(options) {
        ProductShippingCostUpdateWidget.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        const form = this.options._sourceElement.closest('form');

        form.on('change', this.options.unitFormId, this.reloadShippingCostWidget.bind(this));
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.options._sourceElement.off('change');
        ProductShippingCostUpdateWidget.__super__.dispose.call(this);
    },

    /**
     * @param {Event} e
     */
    reloadShippingCostWidget: function(e) {
        const unitId = $(this.options.unitFormId).val();
        const precision = $(this.options.precisionFormId).val();

        widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
            widget.setUrl(
                routing.generate('oro_fixed_product_shipping_widget_shipping_cost_update', {
                    unit: unitId,
                    precision: precision
                })
            );

            widget.render();
        });
    }
});

export default ProductShippingCostUpdateWidget;
