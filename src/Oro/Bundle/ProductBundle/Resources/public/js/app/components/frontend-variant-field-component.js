define(function(require) {
    'use strict';

    var FrontendVariantFieldComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    FrontendVariantFieldComponent = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            simpleProductVariants: []
        },

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
        }
    });

    return FrontendVariantFieldComponent;
});
