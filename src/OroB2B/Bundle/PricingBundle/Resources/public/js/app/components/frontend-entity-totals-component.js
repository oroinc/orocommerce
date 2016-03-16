/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var FrontendEntityTotalsComponent;
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var $ = require('jquery');
    var _ = require('underscore');

    FrontendEntityTotalsComponent = BaseComponent.extend({
        $totalsContainer: {},
        /**
         * @property {Object}
         */
        options: {
            eventsName: [],
            entityClassName: '',
            entityId: 0,
            totalsContainer: '[data-container="totals"]',
            totalsUrl: 'orob2b_pricing_frontend_entity_totals',
            totalsContainerHeader: '<table class="order-checkout-widget__table pull-right"><tbody>',
            totalsContainerFooter: '</tbody></table>',
            subtotalItemTemplate: '<tr><td><%- label %></td><td><%- value %></td></tr>',
            totalItemTemplate: '<tr>' +
                '<td class="order-checkout-widget__total"><span class="text-uppercase"><%- label %></span></td>' +
                '<td class="order-checkout-widget__total"><span class="blue"><%- value %></span></td></tr>'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.$totalsContainer = $(this.options.totalsContainer);

            if (this.options.eventsName.length > 0) {
                var self = this;
                _.each(this.options.eventsName, function(value) {
                    mediator.on(value, self.loadTotals, self);
                });
            }

            this.loadTotals();
        },

        loadTotals: function() {
            var params = {
                entityClassName: this.options.entityClassName,
                entityId: this.options.entityId
            };

            var self = this;
            $.ajax({
                url: routing.generate(this.options.totalsUrl, params),
                type: 'GET',
                success: function (response) {
                    self.updateTotalsContrainer(response);
                },
                error: function(response) {
                    //callback(response);
                }
            });
        },

        updateTotalsContrainer: function(data) {
            var subtotalItemshtml = [];
            var index = 0;
            var html = this.options.totalsContainerHeader;

            var subtotalItemTemplate = _.template(this.options.subtotalItemTemplate);
            var totalItemTemplate = _.template(this.options.totalItemTemplate);

            if (data.subtotals.length > 0) {
                _.each(data.subtotals, function(value) {
                    subtotalItemshtml[index] = subtotalItemTemplate({
                        label: value.label,
                        value: NumberFormatter.formatCurrency(value.amount, value.currency),
                        ftid: index,
                        uid: _.uniqueId('ocs')
                    });

                    index++;
                });
                html += subtotalItemshtml.join('');
            }

            if (!_.isEmpty(data.total)) {
                var value = data.total;
                html += totalItemTemplate({
                    label: value.label,
                    value: NumberFormatter.formatCurrency(value.amount, value.currency),
                    ftid: index,
                    uid: _.uniqueId('ocs')
                });
            }

            html += this.options.totalsContainerFooter;
            this.$totalsContainer.html(html);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            var self = this;
            _.each(this.options.eventsName, function(value) {
                mediator.off(value, self.loadTotals, self);
            });

            FrontendEntityTotalsComponent.__super__.dispose.call(this);
        }
    });

    return FrontendEntityTotalsComponent;
});
