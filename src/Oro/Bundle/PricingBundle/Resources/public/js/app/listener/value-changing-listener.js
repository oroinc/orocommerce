define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');

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

                    const changed = function() {
                        promise.resolve();

                        field.off('value:changed', changed);
                        mediator.off(promiseEvent, setPromise);
                    };

                    const setPromise = function(promises) {
                        promises.push(promise);
                    };

                    field.on('value:changed', changed);
                    mediator.on(promiseEvent, setPromise);
                });
            });
        }
    };

    return ValueChangingListener;
});
