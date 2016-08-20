/*global require*/
require([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'jquery', 'jquery.validate'
    ], function($) {
        $.validator.loadMethod('oroshipping/js/validator/unique-product-unit-shipping-options');
    });
});
