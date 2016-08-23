define(function(require) {
    'use strict';

    var ProductImageTypeRadioControlComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');

    ProductImageTypeRadioControlComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            var form = this.options._sourceElement.closest('form');
            var allRadiosWithImageTypeSelector = 'input[type=radio][data-image-type]:checked';

            form.on('change', allRadiosWithImageTypeSelector, function() {
                var currentType = this.dataset.imageType;
                var withCurrentTypeSelector = 'input[type=radio][data-image-type="' + currentType + '"]:checked';

                form.find(withCurrentTypeSelector).not(this).prop('checked', false);
            });
        }
    });

    return ProductImageTypeRadioControlComponent;
});
