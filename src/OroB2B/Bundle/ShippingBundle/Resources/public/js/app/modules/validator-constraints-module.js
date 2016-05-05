/*global require*/
require([
    'oroui/js/app/controllers/base/controller'
], function (BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'jquery', 'jquery.validate'
    ], function ($) {
        console.log('validator-constraints-module loaded');
        $.validator.loadMethod('orob2bshipping/js/validator/unique-product-unit-shipping-options');
    });
});
