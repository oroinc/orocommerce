define(function(require) {
    'use strict';

    var DataLoadComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    DataLoadComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            events: {
                before: 'entry-point:order:load:before',
                load: 'entry-point:order:load',
                after: 'entry-point:order:load:after',
                listenersOff: 'entry-point:listeners:off',
                listenersOn: 'entry-point:listeners:on'
            },
            entityData: {}
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend(this.options, options);
            mediator.on('page:afterChange', this.updateOrderData, this);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('page:afterChange', this.updateOrderData, this);

            DataLoadComponent.__super__.dispose.call(this);
        },

        updateOrderData: function() {
            mediator.trigger(this.options.events.listenersOff);
            mediator.trigger(this.options.events.before);
            mediator.trigger(this.options.events.load, this.options.entityData);
            mediator.trigger(this.options.events.after);
            mediator.trigger(this.options.events.listenersOn);
        }
    });

    return DataLoadComponent;
});
