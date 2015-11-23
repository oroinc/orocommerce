define(function(require) {
    'use strict';

    var ProductsPricesComponent;
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ProductsPricesComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var $collection = $('.price_lists_collection');
            console.log('init');
            $collection.find('.price-list input[type="hidden"]').on('change', function(e){
                $(this).closest('.price-list').find('.validation-failed').remove();
                $(this).closest('.error').removeClass('error');

                console.log('memory');
            })
        }
    });

    return ProductsPricesComponent;
});