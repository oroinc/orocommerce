define(function(require) {
    'use strict';

    var $ = require('jquery.validate');

    $.validator.loadMethod('oropricing/js/validator/unique-product-prices');
});
