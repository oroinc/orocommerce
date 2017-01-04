define(function(require) {
    'use strict';

    var HeaderRowComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var $ = require('jquery');
    var _ = require('underscore');

    HeaderRowComponent = BaseComponent.extend({
        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$el = $(options._sourceElement);
        },

        /**
         * @inheritDoc
         */
        delegateListeners: function() {
            if (tools.isMobile()) {
                this.listenTo(mediator, 'layout:reposition', _.debounce(this.addScroll, 40));
                this.listenTo(mediator, 'sticky-panel:toggle-state', _.debounce(this.addScroll, 40));
            }
            return HeaderRowComponent.__super__.delegateListeners.apply(this, arguments);
        },

        addScroll: function() {
            var windowHeight = $(window).height();
            var headerRowHeight = this.$el.height();
            var middleBarHeight = this.$el.prev().outerHeight();
            var menuHeight = windowHeight - headerRowHeight;
            var isSticky = this.$el.hasClass('header-row--fixed');
            var $dropdowns = this.$el.find('.header-row__dropdown');

            if (!isSticky) {
                menuHeight = windowHeight - headerRowHeight - middleBarHeight;
            }
            
            $.each($dropdowns, function(index, dropdown) {
                $(dropdown).parent().removeAttr('style');

                var dropdownHeight = $(dropdown).height();

                if (dropdownHeight >= menuHeight) {
                    $(dropdown)
                        .parent()
                        .css('height', menuHeight);
                }
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.$el;

            HeaderRowComponent.__super__.dispose.call(this);
        }
    });

    return HeaderRowComponent;
});
