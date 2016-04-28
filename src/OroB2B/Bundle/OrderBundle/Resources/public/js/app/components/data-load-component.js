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
        options: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, options);
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
            mediator.trigger('entry-point:listeners:off');
            mediator.trigger('entry-point:order:load:before');
            mediator.trigger('entry-point:order:load', this.options);
            mediator.trigger('entry-point:order:load:after');
            mediator.trigger('entry-point:listeners:on');
        }
    });

    return DataLoadComponent;
});
