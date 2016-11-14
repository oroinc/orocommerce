define(function(require) {
    'use strict';

    var LocalizedFieldSlugifyComponent;
    var BaseSlugifyComponent = require('ororedirect/js/app/components/base-slugify-component');

    LocalizedFieldSlugifyComponent = BaseSlugifyComponent.extend({
        /**
         * Populate target and recipient fields with jQuery element(s)
         *
         * @param {Object} options
         */
        initTargetAndRecipient: function(options) {
            this.target = options.target;
            this.recipient = options.recipient;
            this.$target = $('[name^="'+options.target+'[values]"]');
            this.$recipient = $('[name^="'+options.recipient+'[values]"]');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            LocalizedFieldSlugifyComponent.__super__.dispose.call(this);
        }
    });

    return LocalizedFieldSlugifyComponent;
});
