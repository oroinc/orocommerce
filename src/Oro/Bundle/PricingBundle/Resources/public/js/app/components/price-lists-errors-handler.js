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
            var $el = options._sourceElement;
            $el.find('input[type="hidden"]').on('change', function(e){
                $(this).closest('td').find('.validation-failed').remove();
                $(this).closest('.error').removeClass('error');
            })
        }
    });

    return PriceListsComponent;
});