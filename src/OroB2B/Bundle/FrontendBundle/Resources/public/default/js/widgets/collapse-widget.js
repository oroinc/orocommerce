define(['jquery', 'jquery-ui'], function($) {
    'use strict';

    var localStorage = window.localStorage;

    $.widget('oroui.collapseWidget', {
        options: {
            trigger: '[data-collapse-trigger]',
            container: '[data-collapse-container]',
            storageKey: '',
            hasRecords: false,
            open: false,
            openClass: 'expanded',
            animationSpeed: 250
        },

        _create: function() {
            this._super();
            this.$el = this.element;
        },

        _init: function() {
            var storedState = this.options.storageKey ? JSON.parse(localStorage.getItem(this.options.storageKey)) : undefined;

            this.$trigger = this.$el.find(this.options.trigger);
            this.$container = this.$el.find(this.options.container);

            this.options.open = _.isBoolean(storedState) ? storedState : (this.options.hasRecords || this.options.open);

            this.$el.toggleClass(this.options.openClass, this.options.open);

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
            var $trigger = $(event.currentTarget);
            var $container = this.$container;

            if ($trigger.attr('href')) {
                event.preventDefault();
            }

            if ($container.is(':animated')) {
                return false;
            }

            $container.slideToggle(this.options.animationSpeed, function() {
                var isOpen = $(this).is(':visible');
                var params = {
                    isOpen: isOpen,
                    $el: self.$el,
                    $rigger: $trigger,
                    $container: $container
                };

                self.$el.toggleClass(self.options.openClass, isOpen);
                $trigger.trigger('collapse:toggle', params);

                if (self.options.storageKey) {
                    localStorage.setItem(self.options.storageKey, isOpen);
                }
            });
        }
    });

    return 'collapseWidget';
});
