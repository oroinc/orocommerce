/*global require*/
require([
    'oroui/js/app/controllers/base/controller'
], function (BaseController) {
    'use strict';

    /**
     * Init ContentManager's handlers
     */
    BaseController.loadBeforeAction([
        'jquery', 'jquery.validate'
    ], function ($) {
        var constraints = [
            'orob2bvalidation/js/validator/letters',
            'orob2bvalidation/js/validator/alphanumeric',
            'orob2bvalidation/js/validator/url-safe',
            'orob2bvalidation/js/validator/decimal',
            'orob2bvalidation/js/validator/integer',
            'orob2bvalidation/js/validator/greater-than-zero',
            'orob2bvalidation/js/validator/url',
            'orob2bvalidation/js/validator/email'
        ];

        $.validator.loadMethod(constraints);
    });
});
