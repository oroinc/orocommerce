define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var LineItemView = require('orob2bshoppinglist/js/app/views/line-item-view');

    var LineItemsView;

    LineItemsView = BaseView.extend({
        lineItems: [],

        initialize: function() {
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function(items) {
            _.each(items, function(item) {
                if (item.view instanceof LineItemView) {
                    this.lineItems.push(item.view);
                    item.view.on('unit-changed', _.bind(this.unitChanged, this));
                }
            }, this);
        },

        unitChanged: function(data) {
            _.each(this.lineItems, function(lineItem) {
                if (lineItem.lineItemId !== data.lineItemId &&
                    lineItem.model.get('id') === data.product &&
                    lineItem.model.get('unit') === data.unit
                ) {
                    mediator.execute('redirectTo', {url: window.location.href});
                }
            });
        }
    });

    return LineItemsView;
});
