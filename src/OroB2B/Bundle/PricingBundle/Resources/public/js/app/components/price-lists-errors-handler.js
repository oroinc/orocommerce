define(function(require) {
    'use strict';

    var PriceListsComponent;
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

    PriceListsComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var $collection = $('.price_lists_collection');
            $collection.find('.price-list input[type="hidden"]').on('change', function(e){
                $(this).closest('.price-list').find('.validation-failed').remove();
                $(this).closest('.error').removeClass('error');
            })
        }
    });

    return PriceListsComponent;
});