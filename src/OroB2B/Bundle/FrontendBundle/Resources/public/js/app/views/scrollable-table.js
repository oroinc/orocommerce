define(function(require) {
    'use strict';

    var ScrollableTableView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    ScrollableTableView = BaseView.extend({
        defaults: {
            head: '[data-scrollable-content-head]',
            body: '[data-scrollable-content-body]',
            content: '[data-scrollable-content]',
            innerContent: '[data-scrollable-inner-content]',
            itemHasOffset: '[data-scrollable-item-has-offset]',
            offset: 8
        },

        initialize: function(options) {
            ScrollableTableView.__super__.initialize.apply(this, arguments);

            this.render();
        },

        render: function() {
            this.$tableHeadItems = this.$el.find(this.defaults.head).children();
            this.$tableBodyItems = this.$el.find(this.defaults.body).children();

            this.alignCell();
            this.hasScroll();

            mediator.on('scrollable-table:reload', _.bind(function() {
                this.hasScroll();
                this.alignCell();
            }, this));

            $(window).on('resize', _.debounce(_.bind(function() {
                this.alignCell();
            }, this), 200));
        },

        alignCell: function() {
            var self = this;

            this.$tableBodyItems.each(function(index) {
                self.$tableHeadItems
                    .eq(index)
                    .width($(this).width());
            });
        },

        hasScroll: function() {
            var self = this;
            var $scrollableContent = this.$el.find(this.defaults.content);
            var $scrollableInnerContentt = this.$el.find(this.defaults.innerContent);
            var $itemHasOffset = this.$el.find(this.defaults.itemHasOffset);

            // The browser settings should the inner scroll
            if ($scrollableInnerContentt.width() < $scrollableContent.width()) {
                // Has scroll
                if ($scrollableInnerContentt.width() > $scrollableContent.height()) {
                    var scrollWidth = $scrollableContent.width() - $scrollableInnerContentt.width();

                    $itemHasOffset.each(function(index) {
                        $(this).css({
                            'padding-right': index === 0 ? scrollWidth + self.defaults.offset : self.defaults.offset
                        });
                    });
                }
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            mediator.off('scrollable-table:reload');
            $(window).off('resize', this.alignCell());

            ScrollableTableView.__super__.dispose.call(this);
        }
    });

    return ScrollableTableView;
});
