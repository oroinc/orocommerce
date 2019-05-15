define(function(require) {
    'use strict';

    var $ = require('jquery.validate');

    $.validator.loadMethod('oroproduct/js/validator/sku-regex');
});
