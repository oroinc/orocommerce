define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var template = require('tpl!../templates/shopping-list-item-error.html');

    if (typeof template === 'string') {
        template = _.template(template);
    }

    return function(errorMap, errorList) {
        var $container = $(this.currentForm).closest('tr');

        $container.nextAll('[data-role="error-container"]').remove();
        if (errorList.length) {
            _.each(errorMap, function(message) {
                $(template({'message': message})).insertAfter($container);
            });
        }
    };
});
