define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export oroorder/js/app/components/entry-point-component
     * @extends oroui.app.components.base.Component
     * @class oroorder.app.components.EntryPointComponent
     */
    const EntryPointComponent = BaseComponent.extend({
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
                init: 'entry-point:order:init',
                listenersOff: 'entry-point:listeners:off',
                listenersOn: 'entry-point:listeners:on'
            },
            triggerTimeout: 1500
        },

        /**
         * @property {Number}
         */
        timeoutId: null,

        /**
         * @inheritdoc
         */
        constructor: function EntryPointComponent(options) {
            EntryPointComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.initializeListener();

            mediator.on(this.options.events.init, this.initializeListener, this);
            mediator.on(this.options.events.trigger, this.callEntryPoint, this);
            mediator.on(this.options.events.listenersOff, this.listenerOff, this);
            mediator.on(this.options.events.listenersOn, this.listenerOn, this);
        },

        initializeListener: function() {
            this.listenerOff();
            this.listenerOn();
        },

        listenerOff: function() {
            this.options._sourceElement
                .off('change', '[data-entry-point-trigger]')
                .off('keyup', '[data-entry-point-trigger]');
        },

        listenerOn: function() {
            const callback = _.bind(this._sendEntryPointAjax, this);

            const changeCallback = _.bind(function(e) {
                if (this.timeoutId || $(e.target).is('select')) {
                    mediator.trigger(this.options.events.before);
                    callback.call(this);
                }

                this.clearTimeout();
            }, this);

            const keyUpCallback = _.bind(function() {
                this.clearTimeout();

                mediator.trigger(this.options.events.before);
                this.timeoutId = setTimeout(_.bind(callback, this), this.options.triggerTimeout);
            }, this);

            this.options._sourceElement
                .on('change', '[data-entry-point-trigger]', changeCallback)
                .on('keyup', '[data-entry-point-trigger]', keyUpCallback);
        },

        clearTimeout: function() {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);

                this.timeoutId = null;
            }
        },

        callEntryPoint: function() {
            mediator.trigger(this.options.events.before);
            this._sendEntryPointAjax();
        },

        /**
         * @private
         */
        _sendEntryPointAjax: function() {
            const self = this;

            this.listenerOff();

            $.ajax({
                url: routing.generate(this.options.route, this.options.routeParams),
                type: 'POST',
                data: $.param(this.getData()),
                success: function(response) {
                    mediator.trigger(self.options.events.load, response);
                    mediator.trigger(self.options.events.after);
                    self.clearTimeout();
                    self.listenerOn();
                },
                error: function() {
                    mediator.trigger(self.options.events.load, {});
                    mediator.trigger(self.options.events.after);
                    self.clearTimeout();
                    self.listenerOn();
                }
            });
        },

        /**
         * @return {Object}
         */
        getData: function() {
            const disabled = this.options._sourceElement.find('input:disabled[data-entry-point-trigger]')
                .removeAttr('disabled');

            const data = this.options._sourceElement.serializeArray();

            disabled.attr('disabled', 'disabled');

            return data;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(this.options.events.init, this.initializeListener, this);
            mediator.off(this.options.events.trigger, this.callEntryPoint, this);

            this.listenerOff();

            EntryPointComponent.__super__.dispose.call(this);
        }
    });

    return EntryPointComponent;
});
