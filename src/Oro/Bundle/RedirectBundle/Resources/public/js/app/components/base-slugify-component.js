define(function (require) {
    'use strict';

    var SlugifyComponent,
        $ = require('jquery'),
        __ = require('orotranslation/js/translator'),
        _ = require('underscore'),
        routing = require('routing'),
        messenger = require('oroui/js/messenger'),
        BaseComponent = require('oroui/js/app/components/base/component');

    SlugifyComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        $sources: null,

        /**
         * @property {Object}
         */
        $targets: null,

        /**
         * @property {String}
         */
        slugifyRoute: '',

        /**
         * @property {Boolean}
         */
        doSync: true,

        /**
         * Initializes Slugify component
         * @param {Object} options
         */
        initialize: function(options) {
            this.$sources = $(options.source);
            this.$targets = $(options.target);
            this.slugifyRoute = options.slugify_route;

            this.$targets.on('change', _.bind(this.slugTriggerOff, this));
            this.$sources.on('change', _.bind(this.syncField, this));
        },

        /**
         * @param event
         */
        syncField: function(event) {
            throw new Error('Method syncField should be defined in a inherited class.');
        },

        slugifySourceToTarget: function($source, $target) {
            if (!$source.val().trim().length) {
                return;
            }

            $.ajax({
                type: 'GET',
                url: routing.generate(this.slugifyRoute, {'string': $source.val()}),
                success: _.bind(function ($target, $source, result) {
                    if (result.slug) {
                        $target.val(result.slug);
                        $target.change();
                    } else {
                        messenger.notificationFlashMessage(
                            'error',
                            __('oro.redirect.slugify_error', {'string': $source.val()})
                        );
                    }
                }, this, $target, $source)
            });
        },

        /**
         * @param event
         */
        slugTriggerOff: function(event) {
            if (event.originalEvent) {
                this.doSync = false;
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$sources.off('change', _.bind(this.syncField, this));
            this.$targets.off('change', _.bind(this.slugTriggerOff, this));

            SlugifyComponent.__super__.dispose.call(this);
        }
    });

    return SlugifyComponent;
});
