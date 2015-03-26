define(function (require) {
    'use strict';

    var SlugifyComponent,
        $ = require('jquery'),
        __ = require('orotranslation/js/translator'),
        routing = require('routing'),
        messenger = require('oroui/js/messenger'),
        BaseComponent = require('oroui/js/app/components/base/component');

    SlugifyComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        $targetInput: null,

        /**
         * @property {Object}
         */
        $recipientInput: null,

        /**
         * @property {Object}
         */
        $mode: null,

        /**
         * @property {Boolean}
         */
        slugTrigger: true,

        /**
         * Initializes Slugify component
         * @param {Object} options
         */
        initialize: function (options) {
            this.$targetInput = $(options.targetInput);
            this.$recipientInput = $(options.recipientInput);

            var modeName = options.modeName;
            this.$mode = $('input[name="'+modeName+'"]').filter(':radio');

            this.checkSlugAvailable();
            this.$mode.on('change', _.bind(this.checkSlugAvailable, this));

            var currentSlug = this.$recipientInput.val();
            this.$recipientInput.on('keydown', _.bind(this.slugTriggerOff, this, currentSlug));

            var targetInputValue = this.$targetInput.val(),
                timeout;
            var self = this;
            this.$targetInput.on('keyup',function(){
                var value = $(this).val();
                if (value != targetInputValue && self.slugTrigger) {
                    targetInputValue = value;
                    if(timeout) {
                        clearTimeout(timeout);
                    }
                    timeout = setTimeout(function() {
                        if (targetInputValue) {
                            $.ajax({
                                type: 'GET',
                                url: routing.generate('orob2b_api_slugify_slug', {'string': targetInputValue}),
                                success: function (result) {
                                    if (result.slug) {
                                        self.$recipientInput.val(result.slug);
                                    } else {
                                        messenger.notificationFlashMessage(
                                            'error',
                                            __("orob2b.cms.slugify_error", {'string': targetInputValue})
                                        );
                                    }
                                }
                            });
                        }
                    }, 1000);
                }
            });
        },

        /**
         * Check slug is available
         */
        checkSlugAvailable: function() {
            if (this.$mode.filter(':checked').val() === 'old') {
                this.$recipientInput.attr('disabled', 'disabled')
                    .closest('div').find('[type=checkbox]').attr('disabled', 'disabled');
            } else {
                this.$recipientInput.attr("disabled", false)
                    .closest('div').find('[type=checkbox]').attr('disabled', false);
            }
        },

        /**
         * Turn off trigger for slug generation
         */
        slugTriggerOff: function($currentSlug) {
             if ($currentSlug != this.$recipientInput.val() ) {
                this.slugTrigger = false;
            }
        }
    });

    return SlugifyComponent;
});
