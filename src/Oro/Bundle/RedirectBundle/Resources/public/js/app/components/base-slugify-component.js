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
        $target: null,

        /**
         * @property {String}
         */
        target: '',

        /**
         * @property {Object}
         */
        $recipient: null,

        /**
         * @property {String}
         */
        recipient: '',

        /**
         * @property {Boolean}
         */
        doSync: true,

        /**
         * Initializes Slugify component
         * @param {Object} options
         */
        initialize: function(options) {
            this.initTargetAndRecipient(options);
            this.initSlugifyTurningOff();
            this.initSync();
        },

        /**
         * Setup sync of target and receipt fields, on change
         */
        initSync: function() {
            this.$target.on('change', _.bind(this.syncField, this))
        },

        /**
         * Synchronize requested target field with it's recipient.
         * Slugify during this target value by request to the slugify API.
         *
         * @param event
         */
        syncField: function(event) {
            var $target = $(event.target);

            if (!this.doSync) {
                return;
            }

            var $recipient;
            var isBasic = (this.$target.length === 1);
            if (isBasic) {
                $recipient = this.$recipient;
                if ($target.val() === $recipient.val()) {
                    return;
                }
            } else {
                $recipient = this.getRecipientByTarget($target);
                if ($target.is(':disabled')) {
                    return;
                }
                if ($target.prop('type') === 'text' || $target.is('select')) {
                    if ($target.val() === $recipient.val()) {
                        return;
                    }
                } else if ($target.prop('type') === 'checkbox') {
                    if ($target.prop('checked') === $recipient.prop('checked')) {
                        return;
                    }
                }
            }

            if ($target.prop('type') === 'text') {
                $.ajax({
                    type: 'GET',
                    url: routing.generate('oro_api_slugify_slug', {'string': $target.val()}),
                    success: _.bind(function ($recipient, result) {
                        if (result.slug) {
                            $recipient.val(result.slug);
                            $recipient.change();
                        } else {
                            messenger.notificationFlashMessage(
                                'error',
                                __("oro.cms.slugify_error", {'string': targetInputValue})
                            );
                        }
                    }, this, $recipient)
                });
            } else if ($target.prop('type') === 'checkbox') {
                $recipient.prop('checked', $target.prop('checked'));
                $recipient.change();
            } else if ($target.is('select')) {
                $recipient.val($target.val());
                $recipient.change();
            }
        },

        /**
         * Turn off slugify when already not needed
         */
        initSlugifyTurningOff: function() {
            // should be defined in descendants
        },

        /**
         * Populate target and recipient fields with jQuery element(s)
         *
         * @param {Object} options
         */
        initTargetAndRecipient: function(options) {
            // should be defined in descendants
        },

        /**
         * Find out related to the target recipient
         *
         * @param $target
         * @returns {*|jQuery|HTMLElement}
         */
        getRecipientByTarget: function($target) {
            var recipientSelector = $target.prop('name') + '';
            recipientSelector = recipientSelector.replace(this.target, this.recipient);
            return $('[name="'+recipientSelector+'"]');
        },

        /**
         * Find out related to the recipient target
         *
         * @param $recipient
         * @returns {*|jQuery|HTMLElement}
         */
        getTargetByRecipient: function($recipient) {
            var targetSelector = $recipient.prop('name') + '';
            targetSelector = targetSelector.replace(this.recipient, this.target);
            return $('[name="'+targetSelector+'"]');
        }
    });

    return SlugifyComponent;
});
