define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    let template = require('tpl-loader!../templates/shopping-list-item-error.html');

    if (typeof template === 'string') {
        template = _.template(template);
    }

    return function(errorMap, errorList) {
        const $container = $(this.currentForm).closest('tr');

        $container.nextAll('[data-role="error-container"]').remove();
        if (errorList.length) {
            _.each(errorMap, function(message) {
                $(template({message: message})).insertAfter($container);
            });
        }
    };
});
