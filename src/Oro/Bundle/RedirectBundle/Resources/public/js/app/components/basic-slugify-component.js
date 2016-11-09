define(function(require) {
    'use strict';

    var BasicSlugifyComponent;
    var BaseSlugifyComponent = require('ororedirect/js/app/components/base-slugify-component');

    BasicSlugifyComponent = BaseSlugifyComponent.extend({
        /**
         * @property {Object}
         */
        $mode: null,

        /**
         * Initializes Slugify component
         * @param {Object} options
         */
        initialize: function (options) {
            BasicSlugifyComponent.__super__.initialize.call(this, options);

            var modeName = options.modeName;
            this.$mode = $('input[name="'+modeName+'"]').filter(':radio');

            this.checkSlugAvailable();
            this.$mode.on('change', _.bind(this.checkSlugAvailable, this));
        },

        /**
         * Populate target and recipient fields with jQuery element(s)
         *
         * @param {Object} options
         */
        initTargetAndRecipient: function(options) {
            this.target = options.target;
            this.recipient = options.recipient;
            this.$target = $(options.target);
            this.$recipient = $(options.recipient);
        },

        /**
         * Turn off slugify when already not needed
         */
        initSlugifyTurningOff: function() {
            this.$recipient.on('change', _.bind(this.slugTriggerOff, this));
        },

        /**
         * Turn off trigger for slug generation
         */
        slugTriggerOff: function() {
            if (this.$target.val() !== this.$recipient.val() ) {
                this.doSync = false;
            }
        },

        /**
         * Check slug is available
         */
        checkSlugAvailable: function() {
            if (this.$mode.filter(':checked').val() === 'old') {
                this.$recipient.attr('disabled', 'disabled')
                    .closest('div').find('[type=checkbox]').attr('disabled', 'disabled');
            } else {
                this.$recipient.attr("disabled", false)
                    .closest('div').find('[type=checkbox]').attr('disabled', false);
            }
        }
    });

    return BasicSlugifyComponent;
});
