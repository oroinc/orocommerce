define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const TaxFormatter = require('orotax/js/formatter/tax');

    /**
     * @export orotax/js/app/components/order-taxes-component
     * @extends oroui.app.components.base.Component
     * @class orotax.app.components.OrderTaxesComponent
     */
    const OrderTaxesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                totalsTemplate: '#order-taxes-totals-template',
                collapseSelector: '[data-role="order-taxes-totals"]'
            }
        },

        /**
         * @property {Object}
         */
        totalsTemplate: null,

        /**
         * @inheritdoc
         */
        constructor: function OrderTaxesComponent(options) {
            OrderTaxesComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.listenTo(mediator, 'entry-point:order:trigger:totals', this.appendTaxResult);

            this.totalsTemplate = $(this.options.selectors.totalsTemplate).html();
        },

        appendTaxResult: function(totals) {
            const subtotals = _.extend({subtotals: {}}, totals).subtotals;
            _.map(_.where(subtotals, {type: 'tax'}), this.prepareItem.bind(this));
        },

        /**
         * Formats data in a subtotals item
         *
         * @param {Object} item
         * @param {number} index
         */
        prepareItem: function(item, index) {
            item.data.total = TaxFormatter.formatItem(item.data.total);
            item.data.shipping = TaxFormatter.formatItem(item.data.shipping);
            item.data.taxes = _.map(item.data.taxes, TaxFormatter.formatTax);

            item.data.show = $(this.options.selectors.collapseSelector).eq(index).hasClass('show');
            item.template = this.totalsTemplate;
        }
    });

    return OrderTaxesComponent;
});
