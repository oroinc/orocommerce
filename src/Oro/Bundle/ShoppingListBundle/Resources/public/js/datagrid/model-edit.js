import ShoppingListModel from 'oroshoppinglist/js/datagrid/model';

const ShoppingListEditItemModel = ShoppingListModel.extend({
    constructor: function ShoppingListEditItemModel(attributes, options) {
        return ShoppingListEditItemModel.__super__.constructor.call(this, attributes, options);
    },

    highlightDelay: 5000,

    initialize(attributes, options) {
        ShoppingListEditItemModel.__super__.initialize.call(this, attributes, options);
        if (!this.get('isConfigurable')) {
            this.set('unitCode', this.getCurrentModelUnit(), {silent: true});
        }
    },

    getCurrentModelUnit() {
        if (!this.get('units')) {
            return null;
        }
        return Object.values(this.get('units')).find(unit => unit.selected).label;
    },

    highlightRow(type = 'success') {
        const highlightClass = `grid-row--${type}`;
        this.classList().add(highlightClass);
    },

    unhighlightRow(type = 'success', delay = 0) {
        const highlightClass = `grid-row--${type}`;

        if (delay && delay > 0) {
            setTimeout(() => this.classList().remove(highlightClass), delay);
        } else {
            this.classList().remove(highlightClass);
        }
    },

    toggleLoadingOverlay(state) {
        state ? this.highlightRow('loading') : this.unhighlightRow('loading');
    },

    flashRowHighlight(type = 'success', delay = this.highlightDelay) {
        this.highlightRow(type);
        this.unhighlightRow(type, delay);
    }
});

export default ShoppingListEditItemModel;
