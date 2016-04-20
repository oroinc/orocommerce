/** @lends LineItemView */
define(function(require) {
    'use strict';

    var BaseView = require('oroui/js/app/views/base/view');
    var LineItemView = require('orob2bshoppinglist/js/app/views/line-item-view');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    var LineItemsView;

    LineItemsView = BaseView.extend(/** @exports LineItemsView.prototype */{
        $priceContainer: {},

        options: {
            currency: null,
            product: null,
            matchedPricesRoute: null,
            priceContainer: null,
            quantityContainer: null,
            quantityComponentOptions: {
                validation: {
                    showErrorsHandler: 'orob2bshoppinglist/js/shopping-list-item-errors-handler'
                }
            }
        },

        lineItems: [],

        initialize: function(options) {
            this.initLineItems(options.lineItemOptions);
        },

        initLineItems: function(lineItemOptions) {
            var self = this;
            this.$el.find('tr.line_item_view').each(function(key, lineItem) {
                var lineItemObj = $(lineItem);
                var lineItemId = lineItemObj.data('id');
                var product = lineItemObj.data('product-id');
                var currency = lineItemObj.data('currency');
                var unit = lineItemObj.data('unit-code');
                var quantity = lineItemObj.data('quantity');
                lineItemOptions.quantityComponentOptions.value = {'unit': unit, 'quantity': quantity};
                lineItemOptions.quantityComponentOptions.save_api_accessor.default_route_parameters = {
                    'id': lineItemId
                };
                var lineItemView = new LineItemView(
                    _.extend({}, lineItemOptions, {
                        el: lineItem,
                        product: product,
                        currency: currency,
                        lineItemId: lineItemId
                    })
                );
                self.subview(lineItemView);
                self.lineItems.push({
                    lineItemId: lineItemId,
                    product: product,
                    unit: unit
                });
                lineItemView.on('unit-changed', _.bind(self.unitChanged, self));
            });
        },

        unitChanged: function(data) {
            _.each(this.lineItems, function(lineItem) {
                if (lineItem.lineItemId === data.lineItemId) {
                    lineItem.unit = data.unit;
                } else if (lineItem.product === data.product && lineItem.unit === data.unit) {
                    mediator.execute('refreshPage');
                }
            });
        }
    });

    return LineItemsView;
});
