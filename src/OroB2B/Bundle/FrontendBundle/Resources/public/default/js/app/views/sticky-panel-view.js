define(function(require) {
    'use strict';

    var StickyPanelView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    StickyPanelView = BaseView.extend({
        autoRender: true,

        options: {
            placeholderClass: 'moved-to-sticky',
            elementClass: 'in-sticky',
            scrollTimeout: 25,
            layoutTimeout: 40
        },

        $document: null,

        elements: null,

        scrollState: null,

        viewPort: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});
            StickyPanelView.__super__.initialize.apply(this, arguments);

            this.$document = $(document);
            this.elements = [];
            this.scrollState = {
                directionClass: '',
                position: 0
            };
            this.viewPort = {
                top: 0,
                bottom: 0
            };
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function() {
            StickyPanelView.__super__.delegateEvents.apply(this, arguments);

            this.$document.on(
                'scroll' + this.eventNamespace(),
                _.debounce(_.bind(this.onScroll, this), this.options.scrollTimeout)
            );

            mediator.on('layout:reposition',  _.debounce(this.onScroll, this.options.layoutTimeout), this);
            mediator.on('layout:adjustHeight',  _.debounce(this.onScroll, this.options.layoutTimeout), this);

            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            this.$document.off(this.eventNamespace());
            mediator.off(null, null, this);

            return StickyPanelView.__super__.undelegateEvents.apply(this, arguments);
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

            _.each(['$document', '$elements', 'scrollState', 'viewPort'], function(key) {
                delete this[key];
            }, this);

            return StickyPanelView.__super__.dispose.apply(this, arguments);
        },

        collectElements: function() {
            this.elements = $('[data-sticky]').get();
            var $placeholder = this.$el.children();

            _.each(this.elements, function(element, i) {
                var $element = $(element);
                this.elements[i] = $element;

                var $elementPlaceholder = this.createPlaceholder()
                    .data('stickyElement', $element);

                var options = _.defaults($element.data('sticky') || {}, {
                    $elementPlaceholder: $elementPlaceholder,
                    placeholderId: '',
                    toggleClass: ''
                });
                options.$placeholder = options.placeholderId ? $('#' + options.placeholderId) : $placeholder;
                options.toggleClass += ' ' + this.options.elementClass;

                $element.data('sticky', options);
            }, this);

            if (this.elements.length) {
                this.delegateEvents();
            } else {
                this.undelegateEvents();
            }
        },

        createPlaceholder: function() {
            return $('<div/>').addClass(this.options.placeholderClass);
        },

        onScroll: function() {
            this.updateScrollState();
            this.updateViewPort();

            var contentChanged = false;
            for (var i = 0, iMax = this.elements.length; i < iMax; i++) {
                var $element = this.elements[i];

                if ($element.hasClass(this.options.elementClass)) {
                    if (this.inViewPort($element.data('sticky').$elementPlaceholder, $element) || $element.is(':empty')) {
                        contentChanged = true;
                        this.toggleElementState($element, false);
                    }
                } else if (!$element.is(':empty') && !this.inViewPort($element)) {
                    contentChanged = true;
                    this.toggleElementState($element, true);
                }

                if (contentChanged) {
                    break;
                }
            }

            if (contentChanged) {
                this.$el.toggleClass('has-content', this.$el.find('.' + this.options.elementClass).length > 0);
                this.onScroll();
            }
        },

        updateViewPort: function() {
            this.viewPort.top = $(window).scrollTop() + this.$el.height();
            this.viewPort.bottom = this.viewPort.top + $(window).height();
        },

        inViewPort: function($element, $elementInSticky) {
            var elementTop = $element.offset().top;
            var elementBottom = elementTop + $element.height();
            var elementInStickyHeight = $elementInSticky ? $elementInSticky.height() : 0;

            return (
                (elementBottom <= this.viewPort.bottom) &&
                (elementTop >= this.viewPort.top - elementInStickyHeight)
            );
        },

        updateScrollState: function() {
            var position = this.$document.scrollTop();
            var directionClass = this.scrollState.position > position ? 'scroll-up' : 'scroll-down';

            if (this.scrollState.directionClass !== directionClass) {
                this.$el.removeClass(this.scrollState.directionClass)
                    .addClass(directionClass);

                this.scrollState.directionClass = directionClass;
            }

            this.scrollState.position = position;
        },

        toggleElementState: function($element, state) {
            var options = $element.data('sticky');

            if (state) {
                this.updateElementPlaceholder($element);
                $element.addClass(options.toggleClass);
                $element.after(options.$elementPlaceholder);
                options.$placeholder.append($element);
            } else {
                $element.removeClass(options.toggleClass);
                options.$elementPlaceholder.before($element)
                    .remove();
            }

            mediator.trigger('sticky-panel:toggle-state', {$element: $element, state: state});
        },

        updateElementPlaceholder: function($element) {
            $element.data('sticky').$elementPlaceholder.css({
                display: $element.css('display'),
                width: $element.outerWidth(),
                height: $element.outerHeight(),
                margin: $element.css('margin') || 0
            });
        }
    });

    return StickyPanelView;
});
