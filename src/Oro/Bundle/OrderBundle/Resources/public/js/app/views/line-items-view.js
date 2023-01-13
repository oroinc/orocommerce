define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const ProductsPricesComponent = require('oroorder/js/app/components/products-prices-component');
    const BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oroorder/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class oroorder.app.views.LineItemsView
     */
    const LineItemsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            tierPrices: null,
            currency: null,
            customer: null,
            subtotalValidationSelector: '[data-ftid=oro_order_type_subtotalValidation]',
            totalValidationSelector: '[data-ftid=oro_order_type_totalValidation]',
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
         * @inheritdoc
         */
        constructor: function LineItemsView(options) {
            LineItemsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout({
                prices: this.options.tierPrices
            }).done(this.handleLayoutInit.bind(this));

            this.listenTo(mediator, {
                'totals:update': this.updateValidators
            });
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
                currency: this.options.currency,
                customer: this.options.customer
            }));
        },

        updateValidators: function(subtotals) {
            const $subtotal = this.$el.closest('form').find(this.options.subtotalValidationSelector);
            const $total = this.$el.closest('form').find(this.options.totalValidationSelector);
            let subtotalAmount = 0;
            const totalAmount = subtotals.total.amount;

            const self = this;
            _.each(subtotals.subtotals, function(subtotal) {
                if (subtotal.type === self.options.subtotalType) {
                    subtotalAmount = subtotal.amount;
                }
            });

            $subtotal.val(subtotalAmount);
            $total.val(totalAmount);

            const validator = $subtotal.closest('form').validate();

            if (validator) {
                validator.element($subtotal);
                validator.element($total);
            }
        }
    });

    return LineItemsView;
});
