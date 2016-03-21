define(function(require) {
    'use strict';

    var OrderTaxesComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var TaxFormatter = require('orob2btax/js/formatter/tax');

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
                totalsTemplate: '#order-taxes-totals-template',
                collapseSelector: '#order-taxes-totals-table'
            }
        },

        /**
         * @property {Object}
         */
        totalsTemplate: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            mediator.on('entry-point:order:trigger:totals', this.appendTaxResult, this);

            this.totalsTemplate = this.options._sourceElement.find(this.options.selectors.totalsTemplate).html();
        },

        appendTaxResult: function(totals) {
            if (_.isEmpty(totals.subtotals.tax)) {
                return;
            }

            totals.subtotals.tax.data.total = TaxFormatter.formatItem(totals.subtotals.tax.data.total);
            totals.subtotals.tax.data.taxes = _.map(
                totals.subtotals.tax.data.taxes,
                _.bind(TaxFormatter.formatTax, this)
            );

            totals.subtotals.tax.data.in = $(this.options.selectors.collapseSelector).hasClass('in');
            totals.subtotals.tax.template = this.totalsTemplate;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('entry-point:order:trigger:totals', this.appendTaxResult, this);

            OrderTaxesComponent.__super__.dispose.call(this);
        }
    });

    return OrderTaxesComponent;
});
