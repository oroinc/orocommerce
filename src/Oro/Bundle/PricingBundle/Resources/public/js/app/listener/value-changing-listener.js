define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const Backbone = require('backbone');

    /**
     * @export oroorder/js/app/listener/value-changing-listener
     * @class oroorder.app.listener.TotalsListener
     */
    const ValueChangingListener = {
        /**
         * @param {String} promiseEvent
         * @param {jQuery|Array} $fields
         */
        listen: function(promiseEvent, $fields) {
            _.each($fields, function(field) {
                field = $(field).on('value:changing', function() {
                    const promise = $.Deferred();
                    const observer = Object.create(Backbone.Events);

                    const changed = function() {
                        promise.resolve();

                        field.off('value:changed', changed);
                        observer.stopListening();
                    };
                    field.on('value:changed', changed);
                    observer.listenTo(mediator, promiseEvent, promises => promises.push(promise));
                });
            });
        }
    };

    return ValueChangingListener;
});
