define(function(require) {
    'use strict';

    const $ = require('jquery.validate');

    $.validator.loadMethod('oropricing/js/validator/unique-product-prices');
});
