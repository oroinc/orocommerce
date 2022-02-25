define(function(require) {
    'use strict';

    const BaseSlugifyComponent = require('ororedirect/js/app/components/base-slugify-component');
    const $ = require('jquery');

    const LocalizedFieldSlugifyComponent = BaseSlugifyComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function LocalizedFieldSlugifyComponent(options) {
            LocalizedFieldSlugifyComponent.__super__.constructor.call(this, options);
        },

        /**
         * Initializes Slugify component
         * @param {Object} options
         */
        initialize: function(options) {
            LocalizedFieldSlugifyComponent.__super__.initialize.apply(this, [options]);
            this.$targets.on('change', this.syncTargetAfterFallbackValueChanged.bind(this));
        },

        /**
         * @param {Object} event
         */
        syncTargetAfterFallbackValueChanged: function(event) {
            const fallback = $(event.target);
            if (fallback.prop('type') === 'checkbox' && event.originalEvent) {
                if (!fallback.prop('checked')) {
                    const target = this.getTargetByTargetFallBack(fallback);
                    target.val(null);

                    const source = this.getSourceByTargets(target);
                    this.slugifySourceToTarget(source, target);
                }
            }
        },

        /**
         * @param {Object} options
         */
        syncField: function(event) {
            const $source = $(event.target);
            const $target = this.getTargetBySource($source);

            if ($target.is(':disabled') || $source.is(':disabled')) {
                return;
            }

            if ($source.prop('type') === 'checkbox') {
                if ($source.prop('checked') === $target.prop('checked')) {
                    return;
                }
            }

            if ($source.prop('type') === 'text') {
                this.slugifySourceToTarget($source, $target);
                $target.prop('disabled', false);
            } else if ($source.prop('type') === 'checkbox') {
                $target.prop('checked', $source.prop('checked'));
                $target.change();
            }
        },

        /**
         * @param $source
         * @returns {*|jQuery|HTMLElement}
         */
        getTargetBySource: function($source) {
            const sourceIndex = this.$sources.index($source);
            return $(this.$targets.get(sourceIndex));
        },

        /**
         * @param target
         * @returns {*|jQuery|HTMLElement}
         */
        getSourceByTargets: function(target) {
            const targetIndex = this.$targets.index(target);

            return $(this.$sources.get(targetIndex));
        },

        /**
         * @param target
         * @returns {*|jQuery|HTMLElement}
         */
        getTargetByTargetFallBack: function(target) {
            const targetIndex = this.$targets.index(target);

            // The field with value will always precede the fallback field.
            return $(this.$targets.get(targetIndex - 1));
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$targets.off('change', this.syncTargetAfterFallbackValueChanged.bind(this));
            LocalizedFieldSlugifyComponent.__super__.dispose.call(this);
        }
    });

    return LocalizedFieldSlugifyComponent;
});
