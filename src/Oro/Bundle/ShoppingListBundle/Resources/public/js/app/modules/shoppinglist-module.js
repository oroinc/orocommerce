define([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    /**
     * Init Widget Manager's handlers and listeners
     */
    BaseController.loadBeforeAction([
        'oroshoppinglist/js/shoppinglist-request-quote-confirmation'
    ], function() {});
});
