define(function(require) {
    'use strict';

    var OrderTaxesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var NumberFormatter = require('orolocale/js/formatter/number');

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
                applied_taxes_template: '#line-item-taxes-template',
                tableContainer: '[data-table-container]',
                lineItemDataAttr: 'data-tax-item',
                lineItemDataAttrSelector: '[data-tax-item]'
            },
            data: null
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
         * @property {Jquery.Element}
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
                    $.find(this.options.selectors.lineItemDataAttrSelector).length
                );

            mediator.on('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.on('entry-point:order:load', this.setOrderTaxes, this);
            mediator.on('entry-point:order:load:after', this.hideLoadingMask, this);

            this.appliedTaxesTemplate = _.template(
                this.options._sourceElement.find(this.options.selectors.applied_taxes_template).html()
            );

            this.$tableContainer = this.options._sourceElement.find(this.options.selectors.tableContainer);

            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});

            this.render(this.options.data);
        },

        showLoadingMask: function() {
            this.loadingMaskView.show();
        },

        hideLoadingMask: function() {
            this.loadingMaskView.hide();
        },

        render: function(data) {
            var taxData = _.defaults(data, this.emptyData);
            taxData.row = this.formatItem(taxData.row);
            taxData.unit = this.formatItem(taxData.unit);
            taxData.taxes = _.map(taxData.taxes, _.bind(this.formatTax, this));

            this.$tableContainer.html(this.appliedTaxesTemplate(taxData));
        },

        formatItem: function(item) {
            return {
                includingTax: NumberFormatter.formatCurrency(item.includingTax, item.currency),
                excludingTax: NumberFormatter.formatCurrency(item.excludingTax, item.currency),
                taxAmount: NumberFormatter.formatCurrency(item.taxAmount, item.currency)
            };
        },

        formatTax: function(item) {
            return {
                tax: item.tax,
                taxAmount: NumberFormatter.formatCurrency(item.taxAmount, item.currency),
                taxableAmount: NumberFormatter.formatCurrency(item.taxableAmount, item.currency),
                rate: NumberFormatter.formatPercent(item.rate)
            };
        },

        /**
         * @param {Object} response
         */
        setOrderTaxes: function(response) {
            var data = _.defaults(response, {taxesItems: {}});
            var itemId =  this.options._sourceElement.attr(this.options.selectors.lineItemDataAttr);

            if (!_.has(data.taxesItems, itemId)) {
                return;
            }

            var itemData = _.defaults(response.taxesItems[itemId], this.emptyData);

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
            mediator.off('entry-point:order:load', this.setOrderTaxes, this);
            mediator.off('entry-point:order:load:after', this.hideLoadingMask, this);

            OrderTaxesComponent.__super__.dispose.call(this);
        }
    });

    return OrderTaxesComponent;
});
