define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const TaxFormatter = require('orotax/js/formatter/tax');

    /**
     * @export orotax/js/app/components/order-line-item-taxes-component
     * @extends oroui.app.components.base.Component
     * @class orotax.app.components.OrderLineItemTaxesComponent
     */
    const OrderLineItemTaxesComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function OrderLineItemTaxesComponent(options) {
            OrderLineItemTaxesComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.options._sourceElement
                .attr(
                    this.options.selectors.lineItemDataAttr,
                    $(this.options.selectors.lineItemDataAttrSelector).length
                );
            this.listenTo(mediator, {
                'entry-point:order:load:before': () => {
                    this.initializeAttribute();
                    this.showLoadingMask();
                },
                'entry-point:order:load': this.setOrderTaxes,
                'entry-point:order:load:after': this.hideLoadingMask
            });
            this.appliedTaxesTemplate = _.template($(this.options.selectors.appliedTaxesTemplate).html());

            this.$tableContainer = this.options._sourceElement.find(this.options.selectors.tableContainer);

            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});

            this.render(this.options.result);
        },

        initializeAttribute: function() {
            const self = this;
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
            const currency = this.options.productModel.attributes.currency;
            result = _.defaults(result, this.emptyData);
            result.row = TaxFormatter.formatItem(result.row, currency);
            result.unit = TaxFormatter.formatItem(result.unit, currency);
            result.taxes = _.map(result.taxes, TaxFormatter.formatTax);

            this.$tableContainer.html(this.appliedTaxesTemplate(result));
        },

        /**
         * @param {Object} response
         */
        setOrderTaxes: function(response) {
            const result = _.defaults(response, {taxItems: {}});
            const itemId = this.options._sourceElement.attr(this.options.selectors.lineItemDataAttr);

            if (!_.has(result.taxItems, itemId)) {
                return;
            }

            const itemData = _.defaults(response.taxItems[itemId], this.emptyData);

            this.render(itemData);
        }
    });

    return OrderLineItemTaxesComponent;
});
