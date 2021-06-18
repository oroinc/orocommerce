define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const PriceListsComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function PriceListsComponent(options) {
            PriceListsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const $el = options._sourceElement;
            $el.find('input[type="hidden"]').on('change', function(e) {
                $(this).closest('td').find('.validation-failed').remove();
                $(this).closest('.error').removeClass('error');
            });
        }
    });

    return PriceListsComponent;
});
