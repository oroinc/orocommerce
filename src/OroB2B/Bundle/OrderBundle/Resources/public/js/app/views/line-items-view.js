define(function(require) {
    'use strict';

    var LineItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ProductsPricesComponent = require('orob2border/js/app/components/products-prices-component');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2border/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemsView
     */
    LineItemsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            tierPrices: null,
            matchedPrices: {},
            currency: null,
            account: null,
            subtotalValidationSelector: '[data-ftid=orob2b_order_type_subtotalValidation]',
            totalValidationSelector: '[data-ftid=orob2b_order_type_totalValidation]',
            subtotalType: null
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $currency: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));

            mediator.on('totals:update', this.updateValidators, this);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$el.find('.add-list-item').mousedown(function(e) {
                $(this).click();
            });

            this.subview('productsPricesComponent', new ProductsPricesComponent({
                _sourceElement: this.$el,
                tierPrices: this.options.tierPrices,
                matchedPrices: this.options.matchedPrices,
                currency: this.options.currency,
                account: this.options.account
            }));
        },

        updateValidators: function(subtotals) {
            var $subtotal = this.$el.closest('form').find(this.options.subtotalValidationSelector);
            var $total = this.$el.closest('form').find(this.options.totalValidationSelector);
            var subtotalAmount = 0;
            var totalAmount = subtotals.total.amount;

            var self = this;
            _.each(subtotals.subtotals, function(subtotal) {
                if (subtotal.type == self.options.subtotalType) {
                    subtotalAmount = subtotal.amount;
                }
            });

            $subtotal.val(subtotalAmount);
            $total.val(totalAmount);

            var validator = $subtotal.closest('form').validate();

            if (validator) {
                validator.element($subtotal);
                validator.element($total);
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('totals:update', this.updateValidators, this);
            LineItemsView.__super__.dispose.call(this);
        }
    });

    return LineItemsView;
});
