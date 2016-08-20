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
            'orovalidation/js/validator/letters',
            'orovalidation/js/validator/alphanumeric',
            'orovalidation/js/validator/alphanumeric-dash-underscore',
            'orovalidation/js/validator/url-safe',
            'orovalidation/js/validator/decimal',
            'orovalidation/js/validator/integer',
            'orovalidation/js/validator/greater-than-zero',
            'orovalidation/js/validator/url',
            'orovalidation/js/validator/email'
        ];

        $.validator.loadMethod(constraints);
    });
});
