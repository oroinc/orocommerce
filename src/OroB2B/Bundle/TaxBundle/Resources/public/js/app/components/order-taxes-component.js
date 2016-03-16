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
                applied_taxes_template: '.applied-taxes-template'
            }
        },

        /**
         * @property {Object}
         */
        $el: null,

        /**
         * @property {Object}
         */
        appliedTaxesTemplate: null,

        /**
         * @property string
         */
        lineItemDataAttr: 'data-tax-item',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            mediator.on('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.on('entry-point:order:load', this.setOrderTaxes, this);
            mediator.on('entry-point:order:load:after', this.hideLoadingMask, this);

            OrderTaxesComponent.__super__.initialize.call(this, options);
            this.$el = options._sourceElement;
            this.$el.attr(this.lineItemDataAttr, $.find('[' + this.lineItemDataAttr + ']').length);
            this.appliedTaxesTemplate = _.template(this.$el.parent().find(this.options.selectors.applied_taxes_template).text());
        },

        /**
         * @param {Object} response
         */
        setOrderTaxes: function(response) {
            var itemId = this.$el.attr(this.lineItemDataAttr);
            this.setTaxesData(this.$el.find('table').first(), response.taxesItems[itemId]);
        },

        /**
         * @param {Object} $table
         * @param {Array} data
         */
        setTaxesData: function($table, data) {
            if (data) {
                $table.find('[data-taxes-id="unit-including-tax"]').html(data.unit.includingTax);
                $table.find('[data-taxes-id="unit-excluding-tax"]').html(data.unit.excludingTax);
                $table.find('[data-taxes-id="unit-tax-amount"]').html(data.unit.taxAmount);
                $table.find('[data-taxes-id="unit-adjustment"]').html(data.unit.adjustment);
                $table.find('[data-taxes-id="row-including-tax"]').html(data.row.includingTax);
                $table.find('[data-taxes-id="row-excluding-tax"]').html(data.row.excludingTax);
                $table.find('[data-taxes-id="row-tax-amount"]').html(data.row.taxAmount);
                $table.find('[data-taxes-id="row-adjustment"]').html(data.row.adjustment);

                if ($table.next().attr('data-taxes-id') == 'applied-taxes') {
                    $table.next().remove();
                }

                if (data.taxes) {
                    $table.after(this.appliedTaxesTemplate({taxes: data.taxes}));
                }
            }
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
