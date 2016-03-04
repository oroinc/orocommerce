define(function(require) {
    'use strict';

    var ImageTypeRadioControlComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');

    ImageTypeRadioControlComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            //@TODO radio control

            this.options = _.defaults(options || {}, this.options);

            var imageTypeRadioSelector = 'input[name*="orob2b_product[images]"][type=radio]:checked';

            var $form = this.options._sourceElement.closest('form');

            $form.on('change', imageTypeRadioSelector, function(e) {
                $form.find(imageTypeRadioSelector).not(this).prop('checked', false);
            });
        }
    });

    return ImageTypeRadioControlComponent;
});
