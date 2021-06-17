import $ from 'jquery.validate';

$.validator.loadMethod([
    'orovalidation/js/validator/alphanumeric',
    'orovalidation/js/validator/alphanumeric-dash',
    'orovalidation/js/validator/alphanumeric-dash-underscore',
    'orovalidation/js/validator/decimal',
    'orovalidation/js/validator/email',
    'orovalidation/js/validator/greater-than-zero',
    'orovalidation/js/validator/integer',
    'orovalidation/js/validator/letters',
    'orovalidation/js/validator/url',
    'orovalidation/js/validator/url-safe'
]);
