import ShoppingListModel from 'oroshoppinglist/js/datagrid/model';

const ShoppingListEditItemModel = ShoppingListModel.extend({
    constructor: function ShoppingListEditItemModel(attributes, options) {
        return ShoppingListEditItemModel.__super__.constructor.call(this, attributes, options);
    },

    highlightDelay: 1300,

    initialize(attributes, options) {
        this.on('change:quantity change:unit', this.onModelChangeHandler);

        ShoppingListEditItemModel.__super__.initialize.call(this, attributes, options);
        if (!this.get('isConfigurable')) {
            this.set('unitCode', this.get('unit'), {silent: true});
        } else if (this.get('isConfigurable') && !this.get('quantity')) {
            this.set('action_configuration', {
                ...this.get('action_configuration'),
                update_configurable: false
            });
        }
    },

    onModelChangeHandler() {
        if (!this.isSyncedWithEditor()) {
            return;
        }
        const errors = this.get('errors') || [];
        this.flashRowHighlight(errors.length ? 'error' : 'success');
    },

    /**
     * @Get model sync status with editor
     * @Set model sync status with editor
     * @param {boolean} isSynced
     * @returns {boolean}
     */
    isSyncedWithEditor(isSynced) {
        if (typeof isSynced === 'boolean') {
            return isSynced ? this.unset('_state') : this.set('_state', !isSynced);
        }
        return !this.get('_state');
    },

    getMessageModel() {
        return this.collection.get(this.get('messageModelId'));
    },

    /**
     * Get related models by ids
     *
     * @param {string[]} ids
     * @returns
     */
    getRelatedModels(ids = []) {
        return this.collection.filter(model => ids.includes(model.id));
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
    },

    getMinimumQuantity() {
        return this.get('minimumQuantityToOrder') || 0;
    },

    getMaximumQuantity() {
        return this.get('maximumQuantityToOrder') || 1000000000;
    },

    deleteItemAction() {
        const deleteAction = this.get('availableActions')
            .find(({configuration}) => configuration.name === 'delete');

        if (deleteAction) {
            return deleteAction.execute();
        }
    }
});

export default ShoppingListEditItemModel;
