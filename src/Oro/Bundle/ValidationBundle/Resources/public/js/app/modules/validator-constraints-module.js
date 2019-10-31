define(function(require) {
    'use strict';

    const $ = require('jquery.validate');
    const constraints = [
        'orovalidation/js/validator/letters',
        'orovalidation/js/validator/alphanumeric',
        'orovalidation/js/validator/alphanumeric-dash',
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
