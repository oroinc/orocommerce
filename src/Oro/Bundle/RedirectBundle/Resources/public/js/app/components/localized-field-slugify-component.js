define(function(require) {
    'use strict';

    var LocalizedFieldSlugifyComponent;
    var BaseSlugifyComponent = require('ororedirect/js/app/components/base-slugify-component');

    LocalizedFieldSlugifyComponent = BaseSlugifyComponent.extend({
        /**
         * @inheritDoc
         */
        syncField: function(event) {
            var $source = $(event.target);
            var $target;
            $target = this.getTargetBySource($source);

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
            } else if ($source.is('select')) {
                $target.val($source.val());
                $target.change();
            }
        },

        /**
         * @param $source
         * @returns {*|jQuery|HTMLElement}
         */
        getTargetBySource: function($source) {
            var sourceIndex = this.$sources.index($source);
            return $(this.$targets.get(sourceIndex));
        }
    });

    return LocalizedFieldSlugifyComponent;
});
