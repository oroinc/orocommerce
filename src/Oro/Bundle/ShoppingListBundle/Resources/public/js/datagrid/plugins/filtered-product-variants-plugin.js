import BasePlugin from 'oroui/js/app/plugins/base/plugin';

const FilteredProductVariantsPlugin = BasePlugin.extend({
    /**
     * @type {string}
     */
    hideClass: 'hide',

    constructor: function FilteredProductVariantsPlugin(grid, options) {
        FilteredProductVariantsPlugin.__super__.constructor.call(this, grid, options);
    },

    enable: function() {
        if (this.enabled) {
            return;
        }
        this.main.$el.on('click' + this.eventNamespace(), '[data-role="show-all-variants"]', this.onClick.bind(this));
        FilteredProductVariantsPlugin.__super__.enable.call(this);
    },

    disable: function() {
        if (!this.enabled) {
            return;
        }
        this.main.$el.off(this.ownEventNamespace());
        FilteredProductVariantsPlugin.__super__.disable.call(this);
    },

    onClick: function(event) {
        const $button = this.main.$(event.currentTarget);
        const rowSelector = `tr.${this.hideClass}[data-product-group="${$button.data('groupId')}"]`;
        this.main.$(rowSelector).removeClass(this.hideClass);
        $button.hide();
    }
});

export default FilteredProductVariantsPlugin;
