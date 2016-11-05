define(function(require) {
    'use strict';

    var DomRelocationView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    DomRelocationView = BaseView.extend({
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

            DomRelocationView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        setElement: function(element) {
            this.$window = $(window);
            this.elements = null;
            return DomRelocationView.__super__.setElement.call(this, element);
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function() {
            DomRelocationView.__super__.delegateEvents.apply(this, arguments);

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
            return DomRelocationView.__super__.undelegateEvents.apply(this, arguments);
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

            return DomRelocationView.__super__.dispose.apply(this, arguments);
        },

        onResize: function() {
            var windowSize = this.$window.outerWidth();

            if (!this.$elements.length) {
                return;
            }

            _.each(this.$elements, function(el) {
                var $el = $(el);
                var options = this.checkTargetOptions(windowSize, $el.data('responsiveOptions'));

                if (_.isObject(options)) {
                    if ($el.data('targetBreakPoint') !== options.breakpoint) {
                        $(options.moveTo).first().append($el);
                        $el.data('targetBreakPoint', options.breakpoint);
                    }
                } else {
                    $el.data('originalPosition').append($el);
                    $el.data('targetBreakPoint', null);
                }
            }, this);
        },

        checkTargetOptions: function(windowSize, responsiveOptions) {
            var breakpoints = [];

            for (var i = 0; i <= responsiveOptions.length - 1 ; i++) {
                if (windowSize < responsiveOptions[i].breakpoint) {
                    breakpoints.push(responsiveOptions[i]);
                }
            }

            return breakpoints.sort(function(a, b) {
                      return a.breakpoint - b.breakpoint;
                   })[0] || null;
        },

        collectElements: function() {
            this.$elements = $('[data-dom-relocation]');

            if (!this.$elements.length) {
                return ;
            }

            $.each(this.$elements, function(index, element) {
                var $element = $(element);
                $element.data('originalPosition', $element.parent());
                $element.data('responsiveOptions', $element.data('dom-relocation-options').responsive || []);
                $element.data('targetBreakPoint', null);
            });
        }
    });

    return DomRelocationView;
});
