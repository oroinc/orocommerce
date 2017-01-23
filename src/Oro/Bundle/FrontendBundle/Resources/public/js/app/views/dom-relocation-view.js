define(function(require) {
    'use strict';

    var DomRelocationView;
    var viewportManager = require('oroui/js/viewport-manager');
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

            mediator.on('viewport:change', this.onViewportChange, this);
            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            mediator.off(null, null, this);
            this.elements = null;
            return DomRelocationView.__super__.undelegateEvents.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this.collectElements();
            this.onViewportChange(viewportManager.getViewport());
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

        onViewportChange: function(viewportData) {
            if (!this.$elements.length) {
                return;
            }
            _.each(this.$elements, function(el) {
                var $el = $(el);
                var options = this.checkTargetOptions(viewportData, $el.data('responsiveOptions'));

                if (_.isObject(options)) {
                    if ($el.data('targetBreakPoint') !== options.screenType) {
                        $(options.moveTo).first().append($el);
                        $el.data('targetBreakPoint', options.screenType);
                    }
                } else {
                    $el.data('originalPosition').append($el);
                    $el.data('targetBreakPoint', null);
                }
            }, this);
        },

        checkTargetOptions: function(viewportData, responsiveOptions) {
            for (var i = responsiveOptions.length - 1; i >= 0; i--) {
                if (viewportData.screenTypes[responsiveOptions[i].screenType]) {
                    return responsiveOptions[i];
                }
            }
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
