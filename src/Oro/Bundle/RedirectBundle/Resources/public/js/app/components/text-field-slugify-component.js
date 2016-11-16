define(function(require) {
    'use strict';

    var TextFieldSlugifyComponent;
    var BaseSlugifyComponent = require('ororedirect/js/app/components/base-slugify-component');

    TextFieldSlugifyComponent = BaseSlugifyComponent.extend({
        /**
         * @inheritDoc
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
