define(function(require) {
    'use strict';

    var OrderLineItemTaxesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var TaxFormatter = require('orotax/js/formatter/tax');

    /**
     * @export orotax/js/app/components/order-line-item-taxes-component
     * @extends oroui.app.components.base.Component
     * @class orotax.app.components.OrderLineItemTaxesComponent
     */
    OrderLineItemTaxesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                appliedTaxesTemplate: '#line-item-taxes-template',
                tableContainer: '[data-table-container]',
                lineItemDataAttr: 'data-tax-item',
                lineItemDataAttrSelector: '[data-tax-item]'
            },
            result: null
        },

        /**
         * @property {Object}
         */
        appliedTaxesTemplate: null,

        /**
         * @property {Object}
         */
        emptyData: {
            unit: {},
            row: {},
            taxes: {}
        },

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @property {jQuery.Element}
         */
        $tableContainer: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.options._sourceElement
                .attr(
                    this.options.selectors.lineItemDataAttr,
                    $(this.options.selectors.lineItemDataAttrSelector).length
                );

            mediator.on('entry-point:order:load:before', this.initializeAttribute, this);
            mediator.on('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.on('entry-point:order:load', this.setOrderTaxes, this);
            mediator.on('entry-point:order:load:after', this.hideLoadingMask, this);

            this.appliedTaxesTemplate = _.template($(this.options.selectors.appliedTaxesTemplate).html());

            this.$tableContainer = this.options._sourceElement.find(this.options.selectors.tableContainer);

            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});

            this.render(this.options.result);
        },

        initializeAttribute: function() {
            var self = this;
            $(this.options.selectors.lineItemDataAttrSelector).each(function(index) {
                $(this).attr(self.options.selectors.lineItemDataAttr, index);
            });
        },

        showLoadingMask: function() {
            this.loadingMaskView.show();
        },

        hideLoadingMask: function() {
            this.loadingMaskView.hide();
        },

        render: function(result) {
            result = _.defaults(result, this.emptyData);
            result.row = TaxFormatter.formatItem(result.row);
            result.unit = TaxFormatter.formatItem(result.unit);
            result.taxes = _.map(result.taxes, TaxFormatter.formatTax);

            this.$tableContainer.html(this.appliedTaxesTemplate(result));
        },

        /**
         * @param {Object} response
         */
        setOrderTaxes: function(response) {
            var result = _.defaults(response, {taxItems: {}});
            var itemId =  this.options._sourceElement.attr(this.options.selectors.lineItemDataAttr);

            if (!_.has(result.taxItems, itemId)) {
                return;
            }

            var itemData = _.defaults(response.taxItems[itemId], this.emptyData);

            this.render(itemData);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.off('entry-point:order:load:before', this.initializeAttribute, this);
            mediator.off('entry-point:order:load', this.setOrderTaxes, this);
            mediator.off('entry-point:order:load:after', this.hideLoadingMask, this);

            OrderLineItemTaxesComponent.__super__.dispose.call(this);
        }
    });

    return OrderLineItemTaxesComponent;
});
