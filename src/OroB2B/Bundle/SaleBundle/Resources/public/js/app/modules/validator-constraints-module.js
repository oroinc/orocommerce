/*global require*/
require([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    /**
     * Init ContentManager's handlers
     */
    BaseController.loadBeforeAction([
        'jquery', 'jquery.validate'
    ], function($) {
        var constraints = [
            'orob2bsale/js/validator/quote-product-offer-quantity'
        ];

        $.validator.loadMethod(constraints);
    });
});
