define(function(require) {
    'use strict';

    var EntryPointComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2border/js/app/components/entry-point-component
     * @extends oroui.app.components.base.Component
     * @class orob2border.app.components.EntryPointComponent
     */
    EntryPointComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            route: null,
            routeParams: {},
            events: {
                before: 'entry-point:order:load:before',
                load: 'entry-point:order:load',
                after: 'entry-point:order:load:after',
                trigger: 'entry-point:order:trigger',
                init: 'entry-point:order:init'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.initializeListener();

            mediator.on(this.options.events.init, this.initializeListener, this);
            mediator.on(this.options.events.trigger, this.callEntryPoint, this);
        },

        initializeListener: function() {
            this.listenerOff();
            this.listenerOn();
        },

        listenerOff: function() {
            this.options._sourceElement.off('change', '[data-entry-point-trigger]');
        },

        listenerOn: function() {
            this.options._sourceElement.on('change', '[data-entry-point-trigger]', _.bind(this.callEntryPoint, this));
        },

        callEntryPoint: function() {
            var self = this;

            this.listenerOff();
            mediator.trigger(self.options.events.before);

            $.ajax({
                url: routing.generate(this.options.route, this.options.routeParams),
                type: 'POST',
                data: $.param(this.getData()),
                success: function(response) {
                    mediator.trigger(self.options.events.load, response);
                    mediator.trigger(self.options.events.after);
                    self.listenerOn();
                },
                error: function() {
                    mediator.trigger(self.options.events.load, {});
                    mediator.trigger(self.options.events.after);
                    self.listenerOn();
                }
            });
        },

        /**
         * @return {Object}
         */
        getData: function() {
            var disabled = this.options._sourceElement.find('input:disabled[data-entry-point-trigger]')
                .removeAttr('disabled');

            var data = this.options._sourceElement.serializeArray();

            disabled.attr('disabled', 'disabled');

            return data;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(this.options.events.init, this.initializeListener, this);
            mediator.off(this.options.events.trigger, this.callEntryPoint, this);

            EntryPointComponent.__super__.dispose.call(this);
        }
    });

    return EntryPointComponent;
});
