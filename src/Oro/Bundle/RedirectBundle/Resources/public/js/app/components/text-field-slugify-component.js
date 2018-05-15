define(function(require) {
    'use strict';

    var TextFieldSlugifyComponent;
    var BaseSlugifyComponent = require('ororedirect/js/app/components/base-slugify-component');
    var $ = require('jquery');

    TextFieldSlugifyComponent = BaseSlugifyComponent.extend({

        /**
         * @inheritDoc
         */
        constructor: function TextFieldSlugifyComponent() {
            TextFieldSlugifyComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        syncField: function(event) {
            var $source = $(event.target);

            if (!this.doSync) {
                return;
            }

            var $target;
            $target = this.$targets;
            this.slugifySourceToTarget($source, $target);
        }
    });

    return TextFieldSlugifyComponent;
});
