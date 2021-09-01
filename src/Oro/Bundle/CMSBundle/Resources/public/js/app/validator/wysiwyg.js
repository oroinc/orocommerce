define(function() {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');

    return [
        'Oro\\Bundle\\CMSBundle\\Validator\\Constraints\\WYSIWYG',
        function(value, element) {
            const validation = $(element).data('wysiwyg:validation');
            if (!validation) {
                return true;
            }

            return !validation.restrictionValidate().length;
        },
        function({message}, element) {
            const validation = $(element).data('wysiwyg:validation');
            if (!validation) {
                return true;
            }

            const res = validation.restrictionValidate();

            if (res.length) {
                return __(message, {errorsList: res.join(', ')});
            }
        }
    ];
});
