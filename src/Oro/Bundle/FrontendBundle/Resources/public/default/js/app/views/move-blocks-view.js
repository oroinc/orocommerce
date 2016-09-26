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
            resizeTimeout: 250,
            layoutTimeout: 250
        },

        targetBreakpoint: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});

            MoveBlocksView.__super__.initialize.apply(this, arguments);

            this.$window = $(window);
            this.elements = null;
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function() {
            MoveBlocksView.__super__.delegateEvents.apply(this, arguments);

            this.$window.on(
                'resize' + this.eventNamespace(),
                _.debounce(_.bind(this.onResize, this), this.options.resizeTimeout)
            );

            mediator.on('layout:reposition',  _.debounce(this.onResize, this.options.layoutTimeout), this);

            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            this.$window.off(this.eventNamespace());
            mediator.off(null, null, this);
            this.elements = null;
            return MoveBlocksView.__super__.undelegateEvents.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this.collectElements();
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
            var self = this;
            var windowSize = this.$window.outerWidth();

            if (!this.$elements.length) {
                return ;
            }

            $.each(this.$elements, function(index, el) {
                var $el = $(el);
                var options = self.checkTargetOptions(windowSize, $el.data('responsiveOptions'));

                if (_.isObject(options)) {
                    if ($el.data('targetBreakPoint') !== options.breakpoint) {
                        $(options.moveTo).first().append($el);
                        $el.data('targetBreakPoint', options.breakpoint);
                    }
                } else {
                    $el.data('originalPosition').append($el);
                    $el.data('targetBreakPoint', null);
                }
            });
        },

        checkTargetOptions: function(windowSize, responsiveOptions) {
            var breakpoints = [];

            for (var i = 0; i <= responsiveOptions.length -1 ; i++) {
                if (windowSize < responsiveOptions[i].breakpoint ) {
                    breakpoints.push(responsiveOptions[i]);
                }
            }

            return breakpoints.sort(function(a, b) {
                      return a.breakpoint - b.breakpoint;
                   })[0] || null;
        },

        collectElements: function() {
            this.$elements = $('[data-move-block]');

            if (!this.$elements.length) {
                return ;
            }

            $.each(this.$elements, function(index, element) {
                var $element = $(element);
                $element.data('originalPosition', $element.parent());
                $element.data('responsiveOptions', $element.data('move-options').responsive || []);
                $element.data('targetBreakPoint', null);
            });
        }
    });

    return MoveBlocksView;
});
