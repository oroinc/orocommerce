define(function(require) {
    'use strict';

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
                triggerDelayed: 'entry-point:order:trigger-delayed',
                init: 'entry-point:order:init',
                listenersOff: 'entry-point:listeners:off',
                listenersOn: 'entry-point:listeners:on'
            },
            triggerTimeout: 1500
        },

        listenerEnabled: null,

        /**
         * Flag that shows there were some changes in `[data-entry-point-trigger]` fields
         * while the listener was turned off
         * @property {boolean}
         */
        postponedEntryPointAction: false,

        /**
         * @property {Number}
         */
        timeoutId: null,

        listen() {
            return {
                [`${this.options.events.init} mediator`]: 'listenerOn',
                [`${this.options.events.trigger} mediator`]: 'callEntryPoint',
                [`${this.options.events.triggerDelayed} mediator`]: 'callEntryPointDelayed',
                [`${this.options.events.listenersOff} mediator`]: 'listenerOff',
                [`${this.options.events.listenersOn} mediator`]: 'listenerOn'
            };
        },

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
            this.request = null;

            this.initializeListener();
        },

        initializeListener: function() {
            this.options._sourceElement
                .on('change', '[data-entry-point-trigger]', this.callEntryPoint.bind(this))
                .on('keyup', '[data-entry-point-trigger]', this.callEntryPointDelayed.bind(this));
        },

        listenerOff: function() {
            this.listenerEnabled = false;
        },

        listenerOn: function() {
            this.listenerEnabled = true;
            if (this.postponedEntryPointAction) {
                this.postponedEntryPointAction = false;
                this.callEntryPoint();
            }
        },

        clearTimeout: function() {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);

                this.timeoutId = null;
            }
        },

        callEntryPointDelayed: function(e) {
            if (!this.listenerEnabled && e instanceof $.Event) {
                // user input event -- register postponed action
                this.postponedEntryPointAction = true;
                return;
            }

            mediator.trigger(this.options.events.before);
            const callback = () => {
                this._sendEntryPointAjax();
            };

            this.clearTimeout();
            this.timeoutId = setTimeout(callback.bind(this), this.options.triggerTimeout);
        },

        callEntryPoint: function(e) {
            if (!this.listenerEnabled && e instanceof $.Event) {
                // user input event -- register postponed action
                this.postponedEntryPointAction = true;
                return;
            }

            mediator.trigger(this.options.events.before);
            this._sendEntryPointAjax();
        },

        /**
         * @private
         */
        _sendEntryPointAjax: function() {
            if (this.request && this.request.readyState !== 4) {
                return;
            }
            const self = this;

            this.listenerOff();

            this.request = $.post(
                routing.generate(this.options.route, this.options.routeParams),
                $.param(this.getData())
            ).done(function(response) {
                mediator.trigger(self.options.events.load, response);
            }).fail(function() {
                mediator.trigger(self.options.events.load, {});
            }).always(function() {
                mediator.trigger(self.options.events.after);
                self.clearTimeout();
                self.request = null;
                self.listenerOn();
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

            this.listenerOff();

            EntryPointComponent.__super__.dispose.call(this);
        }
    });

    return EntryPointComponent;
});
