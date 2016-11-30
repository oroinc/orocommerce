define(function(require) {
    'use strict';

    var FooterAlignView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    FooterAlignView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            items: '',
            footer: ''
        },

        timeout: 40,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (options.timeout) {
                this.timeout = options.timeout;
            }
            this.initializeElements(options);

            FooterAlignView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function() {
            FooterAlignView.__super__.delegateEvents.apply(this, arguments);
            mediator.on('layout:reposition',  _.debounce(this.alignFooter, this.timeout), this);
            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            mediator.off(null, null, this);
            return FooterAlignView.__super__.undelegateEvents.apply(this, arguments);
        },

        alignFooter: function() {
            this.clearElementsCache();

            _.each(this.getItemsByRow(), this.setAlign, this);
        },

        getItemsByRow: function() {
            var itemsByRow = [];
            var items;
            var previousLeft = 0;

            _.each(this.getElement('items'), function(item) {
                var $item = $(item);

                var $footer = $item.find(this.elements.footer);
                if (!$footer.length) {
                    return;
                }

                var offset = $footer.offset();
                if (!items || offset.left < previousLeft) {
                    items = [];
                    itemsByRow.push(items);
                }
                previousLeft = offset.left;

                items.push({
                    $footer: $footer.css('padding-top', 0),
                    height: offset.top + $footer.outerHeight(true)
                });
            }, this);

            return itemsByRow;
        },

        setAlign: function(items) {
            var maxHeight = _.max(items, function(item) {
                return item.height;
            }).height;

            _.each(items, function(item) {
                var changeHeight = maxHeight - item.height;
                if (changeHeight) {
                    item.$footer.css('padding-top', changeHeight);
                }
            });
        }
    }));

    return FooterAlignView;
});
