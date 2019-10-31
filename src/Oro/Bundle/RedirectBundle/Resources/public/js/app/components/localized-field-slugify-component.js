define(function(require) {
    'use strict';

    const BaseSlugifyComponent = require('ororedirect/js/app/components/base-slugify-component');
    const $ = require('jquery');

    const LocalizedFieldSlugifyComponent = BaseSlugifyComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function LocalizedFieldSlugifyComponent(options) {
            LocalizedFieldSlugifyComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        syncField: function(event) {
            const $source = $(event.target);
            const $target = this.getTargetBySource($source);

            if (!this.doSync) {
                return;
            }

            if ($source.is(':disabled')) {
                return;
            }

            if ($source.prop('type') === 'checkbox') {
                if ($source.prop('checked') === $target.prop('checked')) {
                    return;
                }
            }

            if ($source.prop('type') === 'text') {
                this.slugifySourceToTarget($source, $target);
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
        }
    });

    return LocalizedFieldSlugifyComponent;
});
