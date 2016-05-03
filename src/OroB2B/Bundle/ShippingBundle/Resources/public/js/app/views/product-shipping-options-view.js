define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    return BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                itemsContainer: 'table.list-items',
                itemContainer: 'table tr.list-item'
            }
        },

        /**
         * @property {jQuery}
         */
        itemsContainer: null,

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {
            this.itemsContainer = this.$el.find(this.options.selectors.itemsContainer);

            this.$el
                .on('content:changed', _.bind(this.onContentChanged, this))
                .on('content:remove', _.bind(this.onContentRemoved, this))
            ;

            this.onContentChanged();
        },

        onContentChanged: function() {
            var items = this.$el.find(this.options.selectors.itemContainer);

            this.itemsContainer.toggle(items.length > 0);
        },

        onContentRemoved: function() {
            var items = this.$el.find(this.options.selectors.itemContainer);

            if (items.length <= 1) {
                this.itemsContainer.hide();
            }
        },
    });
});
