define(function(require) {
    'use strict';

    var LocalizedSlugComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LocalizedFieldSlugifyComponent = require('ororedirect/js/app/components/localized-field-slugify-component');
    var ConfirmSlugChangeComponent = require('ororedirect/js/app/components/confirm-slug-change-component');
    var _ = require('underscore');

    LocalizedSlugComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        localizedFieldSlugifyComponent: null,

        /**
         * @property {Object}
         */
        confirmSlugChangeComponent: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var componentOptions;

            if (!_.isUndefined(options.slugify_component_options)) {
                componentOptions = options.slugify_component_options;
                componentOptions._sourceElement = options._sourceElement;
                this.localizedFieldSlugifyComponent = new LocalizedFieldSlugifyComponent(componentOptions);
            }

            if (!_.isUndefined(options.confirmation_component_options)) {
                componentOptions = options.confirmation_component_options;
                componentOptions._sourceElement = options._sourceElement;
                this.confirmSlugChangeComponent = new ConfirmSlugChangeComponent(componentOptions);
            }
        }
    });

    return LocalizedSlugComponent;
});
