define(function(require) {
    'use strict';

    const $ = require('jquery.validate');

    $.validator.loadMethod('oroproduct/js/validator/sku-regex');
});
