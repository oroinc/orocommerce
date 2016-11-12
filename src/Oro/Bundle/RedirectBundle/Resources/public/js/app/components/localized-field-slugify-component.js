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
         * Turn off slugify when already not needed
         */
        initSlugifyTurningOff: function() {
            this.$recipient.on('change', _.bind(this.slugTriggerOff, this));
        },

        /**
         * Turn off trigger for slug generation
         *
         * @param event
         */
        slugTriggerOff: function(event) {
            var $recipient = $(event.target);
            var $target = this.getTargetByRecipient($recipient);

            if ($target.prop('type') === 'text' || $target.is('select')) {
                if ($target.val() !== $recipient.val()) {
                    this.doSync = false;
                }
            } else if ($target.prop('type') === 'checkbox') {
                if ($target.prop('checked') !== $recipient.prop('checked')) {
                    this.doSync = false;
                }
            }
        }
    });

    return LocalizedFieldSlugifyComponent;
});
