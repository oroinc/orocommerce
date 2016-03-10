define(function(require) {
    'use strict';

    var OrderTaxesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2btax/js/app/components/order-taxes-component
     * @extends oroui.app.components.base.Component
     * @class orob2btax.app.components.OrderTaxesComponent
     */
    OrderTaxesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                item_taxes: 'td.order-line-item-taxes'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            mediator.on('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.on('entry-point:order:load', this.setOrderTaxes, this);
            mediator.on('entry-point:order:load:after', this.hideLoadingMask, this);

            OrderTaxesComponent.__super__.initialize.call(this, options);
        },

        initializeListeners: function() {
            // disable parent listeners
        },

        /**
         * @param {Object} response
         */
        setOrderTaxes: function(response) {
            this.renderItems(response.taxesItems);
            this.renderTotal(response.taxesTotal);
        },

        /**
         * @param {Array} taxesItems
         */
        renderItems: function(taxesItemsData) {
            var itemsTaxesNodes = $(this.options.selectors.item_taxes);
            _.each(itemsTaxesNodes, function(itemsTaxesNode) {
                var node = $(itemsTaxesNode);
                var data = taxesItemsData.shift();
                node.find('[data-item-taxes-id="unit-including-tax"]').html(data.unit.includingTax);
                node.find('[data-item-taxes-id="unit-excluding-tax"]').html(data.unit.excludingTax);
                node.find('[data-item-taxes-id="unit-tax-amount"]').html(data.unit.taxAmount);
                node.find('[data-item-taxes-id="unit-adjustment"]').html(data.unit.adjustment);
                node.find('[data-item-taxes-id="row-including-tax"]').html(data.row.includingTax);
                node.find('[data-item-taxes-id="row-excluding-tax"]').html(data.row.excludingTax);
                node.find('[data-item-taxes-id="row-tax-amount"]').html(data.row.taxAmount);
                node.find('[data-item-taxes-id="row-adjustment"]').html(data.row.adjustment);
            }, this);
        },

        /**
         * @param {Object} taxesTotal
         */
        renderTotal: function(taxesTotalData) {

        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.off('entry-point:order:load', this.setOrderTaxes, this);
            mediator.off('entry-point:order:load:after', this.hideLoadingMask, this);

            OrderTaxesComponent.__super__.dispose.call(this);
        }
    });

    return OrderTaxesComponent;
});
