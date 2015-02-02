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
        var attributeConstraints = [
            'orob2battribute/js/validator/letters',
            'orob2battribute/js/validator/alphanumeric',
            'orob2battribute/js/validator/url-safe',
            'orob2battribute/js/validator/decimal',
            'orob2battribute/js/validator/integer'
        ];

        $.validator.loadMethod(attributeConstraints);
    });
});
