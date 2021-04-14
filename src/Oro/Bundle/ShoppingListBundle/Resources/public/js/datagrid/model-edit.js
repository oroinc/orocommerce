import ShoppingListModel from 'oroshoppinglist/js/datagrid/model';

const ShoppingListEditItemModel = ShoppingListModel.extend({
    constructor: function ShoppingListEditItemModel(attributes, options) {
        return ShoppingListEditItemModel.__super__.constructor.call(this, attributes, options);
    },

    highlightDelay: 1300,

    initialize(attributes, options) {
        ShoppingListEditItemModel.__super__.initialize.call(this, attributes, options);
        if (!this.get('isConfigurable')) {
            this.set('unitCode', this.get('unit'), {silent: true});
        }
    },

    getMessageModel() {
        return this.collection.get(this.get('messageModelId'));
    },

    highlightRow(type = 'success') {
        this.classList().add(type);
        const messageModel = this.getMessageModel();
        if (messageModel) {
            messageModel.highlightRow(type);
        }
    },

    unhighlightRow(type = 'success', delay = 0) {
        if (delay && delay > 0) {
            setTimeout(() => this.classList().remove(type), delay);
        } else {
            this.classList().remove(type);
        }

        const messageModel = this.getMessageModel();
        if (messageModel) {
            messageModel.unhighlightRow(type, delay);
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
