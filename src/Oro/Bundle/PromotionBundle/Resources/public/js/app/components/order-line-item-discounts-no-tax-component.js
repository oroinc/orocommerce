define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const NumberFormatter = require('orolocale/js/formatter/number');

    /**
     * @export orotax/js/app/components/order-line-item-discounts-no-tax-component
     * @extends oroui.app.components.base.Component
     * @class orotax.app.components.OrderLineItemAppliedDiscountsComponent
     */
    const OrderLineItemAppliedDiscountsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                rowTotalAfterDiscount: '.applied-discount-row-total-after-discount',
                appliedDiscountsAmount: '.applied-discount-row-total-discount-amount'
            }
        },

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritdoc
         */
        constructor: function OrderLineItemAppliedDiscountsComponent(options) {
            OrderLineItemAppliedDiscountsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.listenTo(mediator, {
                'entry-point:order:load:before': this.showLoadingMask,
                'entry-point:order:load': this.setDiscounts,
                'entry-point:order:load:after': this.hideLoadingMask
            });
            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});
        },

        showLoadingMask: function() {
            this.loadingMaskView.show();
        },

        hideLoadingMask: function() {
            this.loadingMaskView.hide();
        },

        /**
         * @param {Object} response
         */
        setDiscounts: function(response) {
            const itemId = this.options._sourceElement.closest('tr.order-line-item').index();
            if (!_.has(response.appliedDiscounts, itemId)) {
                return;
            }
            const discounts = response.appliedDiscounts[itemId];
            const appliedDiscountsAmount = NumberFormatter.formatCurrency(
                discounts.appliedDiscountsAmount,
                discounts.currency
            );
            const rowTotalAfterDiscount = NumberFormatter.formatCurrency(
                discounts.rowTotalAfterDiscount,
                discounts.currency
            );
            this.options._sourceElement
                .find(this.options.selectors.appliedDiscountsAmount)
                .text(appliedDiscountsAmount);
            this.options._sourceElement
                .find(this.options.selectors.rowTotalAfterDiscount)
                .text(rowTotalAfterDiscount);
        }
    });

    return OrderLineItemAppliedDiscountsComponent;
});
