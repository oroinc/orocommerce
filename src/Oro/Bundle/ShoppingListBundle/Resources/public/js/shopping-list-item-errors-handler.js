import $ from 'jquery';
import _ from 'underscore';
import template from 'tpl-loader!../templates/shopping-list-item-error.html';

export default function(errorMap, errorList) {
    const $container = $(this.currentForm).closest('tr');

    $container.nextAll('[data-role="error-container"]').remove();
    if (errorList.length) {
        _.each(errorMap, function(message) {
            $(template({message: message})).insertAfter($container);
        });
    }
};
