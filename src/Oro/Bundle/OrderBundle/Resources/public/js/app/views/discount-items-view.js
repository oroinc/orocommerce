define(function(require) {
    'use strict';

    var DiscountItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oroorder/js/app/views/discount-items-view
     * @extends oroui.app.views.base.View
     * @class oroorder.app.views.DiscountItemsView
     */
    DiscountItemsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            discountsSumSelector: '[data-ftid=oro_order_type_discountsSum]',
            discountType: null,
            totalType: null
        },

        /**
         * @property {jQuery.Element}
         */
        $discountsSumElement: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));

            this.$discountsSumElement = this.$el.find(this.options.discountsSumSelector);

            mediator.on('totals:update', this.updateSumAndValidators, this);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$el.find('.add-list-item').mousedown(function(e) {
                $(this).click();
            });
        },

        updateSumAndValidators: function(subtotals) {
            var dataValidation = this.$discountsSumElement.data('validation');
            var discountsSum = 0;
            var total = 0;

            var self = this;
            _.each(subtotals.subtotals, function(subtotal) {
                if (subtotal.type === self.options.discountType) {
                    discountsSum += subtotal.amount;
                }

                if (subtotal.type === self.options.totalType) {
                    total = subtotal.amount;
                }
            });

            this.$discountsSumElement.val(discountsSum);

            if (dataValidation && !_.isEmpty(dataValidation.Range)) {
                dataValidation.Range.max = total;
            }

            var validator = $(this.$discountsSumElement.closest('form')).validate();
            validator.element(this.$discountsSumElement);
        },
        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('totals:update', this.updateSumAndValidators, this);
            DiscountItemsView.__super__.dispose.call(this);
        }
    });

    return DiscountItemsView;
});
