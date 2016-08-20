define(function(require) {
    'use strict';

    var ValueChangingListener;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    /**
     * @export oroorder/js/app/listener/value-changing-listener
     * @class oroorder.app.listener.TotalsListener
     */
    ValueChangingListener = {
        /**
         * @param {String} promiseEvent
         * @param {jQuery|Array} $fields
         */
        listen: function(promiseEvent, $fields) {
            _.each($fields, function(field) {
                field = $(field).on('value:changing', function() {
                    var promise = $.Deferred();

                    var changed = function() {
                        promise.resolve();

                        field.off('value:changed', changed);
                        mediator.off(promiseEvent, setPromise);
                    };

                    var setPromise = function(promises) {
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
