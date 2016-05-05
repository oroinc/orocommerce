/** @lends LineItemView */
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var LineItemView = require('orob2bshoppinglist/js/app/views/line-item-view');

    var LineItemsView;

    LineItemsView = BaseView.extend(/** @exports LineItemsView.prototype */{
        lineItems: [],

        initialize: function() {
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function(params) {
            var self = this;
            var items = params;

            _.each(items, function(item) {
                if (item.view instanceof LineItemView) {
                    self.lineItems.push(item.view);
                    item.view.on('unit-changed', _.bind(self.unitChanged, self));
                }
            });
        },

        unitChanged: function(data) {
            _.each(this.lineItems, function(lineItem) {
                if (lineItem.options.lineItemId === data.lineItemId) {
                    lineItem.options.unitCode = data.unit;
                } else if (lineItem.options.product === data.product && lineItem.unit === data.unit) {
                    mediator.execute('refreshPage');
                }
            });
        }
    });

    return LineItemsView;
});
