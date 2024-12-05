import $ from 'jquery';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';

const ProductKitExpandCollapseItems = BasePlugin.extend({
    /**
     * @type {string}
     */
    hideClass: 'hide',

    /**
     * @type {string}
     */
    collapsedClass: 'product-kit-row-collapsed',

    constructor: function ProductKitExpanCollapseItems(grid, options) {
        ProductKitExpanCollapseItems.__super__.constructor.call(this, grid, options);
    },

    enable() {
        if (this.enabled) {
            return;
        }

        this.main.$el.on(`click${this.eventNamespace()}`, '[data-role="expand-kits"]', this.onClick.bind(this));
        ProductKitExpandCollapseItems.__super__.enable.call(this);
    },

    disable() {
        if (!this.enabled) {
            return;
        }
        this.main.$el.off(this.ownEventNamespace());
        ProductKitExpandCollapseItems.__super__.disable.call(this);
    },

    onClick(event) {
        const $row = this.main.$(event.currentTarget).parents('.grid-row');
        const isCollapsed = $row.hasClass(this.collapsedClass);
        const buttonsSelector = '[data-role="expand-kits"]';

        let $subRows = $();
        let $nextRow = $row.next();
        while ($nextRow.is('.sub-row-product-kit')) {
            $subRows = $subRows.add($nextRow);
            $nextRow = $nextRow.next();
        }

        if (isCollapsed) {
            $row.find(buttonsSelector).each((i, el) => {
                const $label = $(el).find('[data-label]');

                $(el).removeClass('collapsed');
                $label.text($label.data('labelExpanded'));
            });

            $row.removeClass(this.collapsedClass);
            $subRows.removeClass(this.hideClass);
        } else {
            $row.find(buttonsSelector).each((i, el) => {
                const $label = $(el).find('[data-label]');

                $(el).addClass('collapsed');
                $label.text($label.data('labelCollapsed'));
            });
            $row.addClass(this.collapsedClass);
            $subRows.addClass(this.hideClass);
        }
    }
});

export default ProductKitExpandCollapseItems;
