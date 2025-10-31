import BaseSlugifyComponent from 'ororedirect/js/app/components/base-slugify-component';
import $ from 'jquery';

const TextFieldSlugifyComponent = BaseSlugifyComponent.extend({

    /**
     * @inheritdoc
     */
    constructor: function TextFieldSlugifyComponent(options) {
        TextFieldSlugifyComponent.__super__.constructor.call(this, options);
    },

    /**
     * @param {Object} options
     */
    syncField: function(event) {
        const $source = $(event.target);

        if (!this.doSync) {
            return;
        }

        const $target = this.$targets;
        this.slugifySourceToTarget($source, $target);
    }
});

export default TextFieldSlugifyComponent;
