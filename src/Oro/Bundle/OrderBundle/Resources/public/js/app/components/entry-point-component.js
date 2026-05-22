import $ from 'jquery';
import _ from 'underscore';
import routing from 'routing';
import mediator from 'oroui/js/mediator';
import BaseComponent from 'oroui/js/app/components/base/component';

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
            listenersOn: 'entry-point:listeners:on',
            interruptPostpone: 'entry-point:interrupt:postpone'
        },
        triggerTimeout: 1500,
        triggerSelector: '[data-entry-point-trigger]',
        skipTriggerSelector: '[data-skip-entry-point-trigger]',
        // Selector to detect if the field that triggered entry point is marked to trigger
        // the recalculation (subtotals, shipping cost, etc.).
        recalculationRequiredSelector: '[data-entry-point-trigger]',
        // Selector of the hidden input holding the recalculation required flag that should be set to 1
        // when the entry point is triggered by fields marked correspondingly.
        recalculationRequiredField: '[name$="[recalculationRequired]"]'
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
            [`${this.options.events.listenersOn} mediator`]: 'listenerOn',
            [`${this.options.events.interruptPostpone} mediator`]: 'interruptPostpone'
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
        this.callEntryPoint = _.debounce(this.callEntryPoint.bind(this), 100);

        this.initializeListener();
    },

    initializeListener: function() {
        this.options._sourceElement
            .on('change', this.options.triggerSelector, this.callEntryPoint.bind(this))
            .on('keyup', this.options.triggerSelector, this.callEntryPointDelayed.bind(this));
    },

    interruptPostpone() {
        this.postponedEntryPointAction = false;
    },

    listenerOff: function() {
        this.listenerEnabled = false;
    },

    listenerOn: function() {
        this.listenerEnabled = true;
        if (this.postponedEntryPointAction) {
            this.postponedEntryPointAction = false;
            this.callEntryPoint({recalculationRequired: this.isRecalculationRequired()});
        }
    },

    clearTimeout: function() {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);

            this.timeoutId = null;
        }
    },

    /**
     * Handles event validation and preprocessing before entry point execution
     *
     * @returns {boolean} Returns false if execution should be skipped, true otherwise.
     * @private
     */
    _handleEntryPointEvent: function(e) {
        if (e instanceof $.Event) {
            const $target = $(e.target);
            if ($target.is(this.options.recalculationRequiredField)) {
                // Skips when triggered by recalculationRequired field.
                return false;
            }

            if ($target.is(this.options.skipTriggerSelector)) {
                // Skips when complies with skip trigger selector.
                return false;
            }

            // Once recalculationRequired flag is on - it should not be changed until the entry point is executed.
            if (!this.isRecalculationRequired()) {
                // Sets the recalculationRequired field value to 1 if the field that triggered entry point is marked
                // to trigger the recalculation (subtotals, shipping cost, etc.).
                this.setRecalculationRequired($target.is(this.options.recalculationRequiredSelector));
            }

            if (!this.listenerEnabled) {
                // User input event - register postponed action.
                this.postponedEntryPointAction = true;
                mediator.execute('isRequestPending', true);

                return false;
            }
        } else if (typeof e === 'object' && e.hasOwnProperty('recalculationRequired')) {
            // Once recalculationRequired flag is on - it should not be changed until the entry point is executed.
            if (!this.isRecalculationRequired()) {
                this.setRecalculationRequired(e.recalculationRequired);
            }
        } else {
            this.setRecalculationRequired(true);
        }

        mediator.execute('isRequestPending', true);

        return true;
    },

    callEntryPointDelayed: function(e) {
        if (!this._handleEntryPointEvent(e)) {
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
        if (!this._handleEntryPointEvent(e)) {
            return;
        }

        mediator.trigger(this.options.events.before);
        this._sendEntryPointAjax();
    },

    /**
     * @returns {Boolean}
     */
    isRecalculationRequired() {
        return $(this.options._sourceElement)
            .find(this.options.recalculationRequiredField)
            .val() === '1';
    },

    /**
     * @param {Boolean} value
     */
    setRecalculationRequired(value) {
        $(this.options._sourceElement)
            .find(this.options.recalculationRequiredField)
            .val(value ? '1' : '0')
            .trigger('change');
    },

    /**
     * @private
     */
    _sendEntryPointAjax: function() {
        if (this.disposed || this.request && this.request.readyState !== 4) {
            return;
        }

        this.listenerOff();

        this.request = $.post(
            routing.generate(this.options.route, this.options.routeParams),
            $.param(this.getData())
        ).done(response => {
            mediator.trigger(this.options.events.load, response);
        }).fail(() => {
            mediator.trigger(this.options.events.load, {});
        }).always(() => {
            mediator.trigger(this.options.events.after);
            this.setRecalculationRequired(false);

            if (this.disposed) {
                return;
            }

            this.clearTimeout();
            this.request = null;
            this.listenerOn();
        });
    },

    /**
     * @return {Object}
     */
    getData: function() {
        const disabled = this.options._sourceElement.find('input:disabled[data-entry-point-trigger]')
            .prop('disabled', false);

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

export default EntryPointComponent;
