define(function(require) {
    'use strict';

    var MoveBlocksView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    MoveBlocksView = BaseView.extend({
        autoRender: true,

        options: {
            scrollTimeout: 25,
            layoutTimeout: 40
        },

        targetBreakpoint: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});

            MoveBlocksView.__super__.initialize.apply(this, arguments);

            this.$document = $(document);
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function() {
            MoveBlocksView.__super__.delegateEvents.apply(this, arguments);

            this.$document.on(
                'resize' + this.eventNamespace(),
                _.debounce(_.bind(this.onResize, this), this.options.scrollTimeout)
            );

            mediator.on('layout:reposition',  _.debounce(this.onResize, this.options.layoutTimeout), this);

            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            this.$document.off(this.eventNamespace());
            mediator.off(null, null, this);

            return MoveBlocksView.__super__.undelegateEvents.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            return this;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            _.each(this.elements, function($element) {
                if ($element.hasClass(this.options.elementClass)) {
                    this.toggleElementState($element, false);
                }
            }, this);

            this.undelegateEvents();

            return MoveBlocksView.__super__.dispose.apply(this, arguments);
        },

        onResize: function() {
            this.checkResponsive();
        },

        checkResponsive: function(breakpoint, breakpoints, respondToWidth) {
            for (breakpoint in breakpoints) {
                if (breakpoints.hasOwnProperty(breakpoint)) {
                    if (respondToWidth < breakpoints[breakpoint]) {
                        this.targetBreakpoint = _.breakpoints[breakpoint];
                    }
                }
            }
        }
    });

    return MoveBlocksView;
});
