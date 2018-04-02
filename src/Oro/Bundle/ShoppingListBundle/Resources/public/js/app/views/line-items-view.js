define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var LineItemView = require('oroshoppinglist/js/app/views/line-item-view');

    var LineItemsView;

    LineItemsView = BaseView.extend({
        lineItems: [],

        /**
         * @inheritDoc
         */
        constructor: function LineItemsView() {
            LineItemsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.initLayout().done(_.bind(this.handleLayoutInit, this));

            mediator.setHandler('get-line-items', _.bind(function() {
                return this.lineItems;
            }, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function(items) {
            this.lineItems = [];
            _.each(items, function(item) {
                if (item.view instanceof LineItemView) {
                    this.lineItems.push(item.view);
                    item.view.on('unit-changed', _.bind(this.unitChanged, this));
                }
            }, this);

            mediator.trigger('line-items-init', this.lineItems);
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
