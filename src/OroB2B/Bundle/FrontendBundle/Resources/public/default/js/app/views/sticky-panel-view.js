define(function (require) {
    'use strict';

    var StickyPanel;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    StickyPanel = BaseView.extend({
        options: {
            eventNameSpace: '.stikypanel',
            placeholderClass: 'moved-to-sticky'
        },

        /**
         * @inheritDoc
         */
        initialize: function(params) {
            this.options = _.extend({}, this.options, params || {});
            this.$elements = null;
            this.currentState = null;
            this.currentPosition = 0;

            StickyPanel.__super__.initialize.apply(this, arguments);
            this.render();

            mediator.trigger('sticky-panel:init');
        },

        render: function() {
            this.$document = $(document);
            this.$elements = $('[data-sticky]');

            this.attachEvents();

            return this;
        },

        attachEvents: function() {
            this.$document.on('scroll' + this.options.eventNameSpace, _.debounce(_.bind(this.onScroll, this), 25));
            mediator.on('layout:reposition',  _.debounce(_.bind(this.onScroll, this), 40));
            mediator.on('layout:adjustHeight',  _.debounce(_.bind(this.onScroll, this), 40));
        },

        onScroll: function() {
            var self = this;
            this.scrollTo();

            if (!this.$elements.length) {
                return ;
            }

            $.each(this.$elements, function() {
                var $element = $(this);
                var params = {
                    height: $element.outerHeight(),
                    margin: $element.css('margin') || 0
                };
                var placeholder = self.createPlaceholder(params);
                var $currentPlaceholder = $element.data('currentPlaceholder');

                if (!self.inViewPort($element)) {
                    $element.data('currentPlaceholder', placeholder);
                    placeholder.data('currentElement', $element);
                    self.toggleState($element, placeholder, true);
                }

                if ($currentPlaceholder && self.inViewPort($currentPlaceholder)) {
                    var $currentElement = $currentPlaceholder.data('currentElement');

                    self.toggleState($currentElement, $currentPlaceholder, false);
                }
            });

            this.hasChildren();
        },

        createPlaceholder: function(obj) {
            return $('<div/>')
                    .addClass(this.options.placeholderClass)
                    .css({
                        'height': obj.height,
                        'margin': obj.margin
                    });
        },

        scrollTo: function() {
            var state = this.currentPosition > this.$document.scrollTop() ? 'scroll-up' : 'scroll-down';

            this.$el.removeClass(this.currentState);
            this.$el.addClass(state);
            this.currentState = state;
            this.currentPosition = this.$document.scrollTop();
            mediator.trigger('sticky-panel:scrollTo', state);
            return state;
        },

        inViewPort: function($element) {
            var windowTop = $(window).scrollTop();
            var windowBottom = windowTop + $(window).height();
            var elementTop = $element.offset().top;
            var elementBottom = elementTop + $element.height();

            return ((elementBottom <= windowBottom) && (elementTop >= windowTop));
        },

        hasChildren: function() {
            var hasChildren =  !!_.size(this.$elements.filter(function() {
                return $(this).closest('.sticky-panel').length;
            }));

            this.$el.toggleClass('has-content', hasChildren);

            return hasChildren;
        },

        toggleState: function($element, placeholder, state) {
            if (!$element) {
                return ;
            }
            var options = $element.data('sticky');
            var $container = this.$el.children();

            if (_.isObject(options)) {
                $container = options.placeholder ? $('#' + options.placeholder) : $container;

                $element.toggleClass(options.toggleClass, state);
            }

            if (state) {
                if ($element.is(':empty')) {
                    return ;
                }

                $element.after(placeholder);
                $container.append($element);
            } else {
                placeholder.before($element);
                placeholder.remove();
            }

            mediator.trigger('sticky-panel:toggle-state', {element: $element, state: state});
        },

        dispose: function() {
            this.$elements = null;
            this.currentState = null;
            this.currentPosition = 0;
            this.$document.off(this.options.eventNameSpace);
        }
    });

    return StickyPanel;
});
