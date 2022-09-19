define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const DataLoadComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function DataLoadComponent(options) {
            DataLoadComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.extend(this.options, options);
            this.listenTo(mediator, {
                'page:afterChange': this.updateOrderData
            });
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
