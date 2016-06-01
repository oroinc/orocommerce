define(['jquery', 'jquery-ui'], function($) {
    'use strict';

    /**
     * Condition builder widget
     */
    $.widget('oroui.collapseWidget', {

        options: {
            trigger: '[data-collapse-trigger]',
            containerCollapse: '[data-collapse-container]',
            open: false,
            openClass: 'expanded',
            animationSpeed: 250,
            animationClass: 'animate'
        },

        _create: function() {
            this._super();
            this.$el = this.element;
        },

        _init: function() {
            this.$trigger = this.$el.find(this.options.trigger);
            this.$containerCollapse = this.$el.find(this.options.containerCollapse);

            if (this.options.open) {
                this.$containerCollapse.addClass(this.options.openClass);
            }

            this.$el.addClass('init');

            this._initEvents();
        },

        _initEvents: function() {
            this._on(this.$trigger, {
                'click': this._toggle
            });
        },

        _toggle: function(event) {
            var self = this;
            var $this = $(event.currentTarget);

            if ($this.attr('href')) {
                event.preventDefault();
            }

            if (this.$containerCollapse.is(':animated')) {
                return false;
            }

            this.$containerCollapse
                .slideToggle(this.options.animationSpeed, function() {
                    var $that = $(this);
                    var isOpen = $that.is(':visible');
                    var params = {
                        isOpen: isOpen,
                        container: self.$el,
                        containerCollapse: self.$containerCollapse,
                        currentTrigger: $this
                    };

                    self.$el.toggleClass(self.options.openClass, isOpen);
                    $this.trigger('collapse:toggle', params);
                });
        }
    });
});
