import ShoppingListEditItemModel from 'oroshoppinglist/js/datagrid/model-edit';
import ShoppingListEditors from 'oroshoppinglist/js/inline-editing/shopping-list-editors';

const shoppingListEditingBuilder = {
    processDatagridOptions(deferred, options) {
        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }

        options.metadata.inline_editing.default_editors = ShoppingListEditors;
        options.metadata.options.model = ShoppingListEditItemModel;

        deferred.resolve();
        return deferred;
    },

    /**
     * Init() function is required
     */
    init: deferred => deferred.resolve()
};

export default shoppingListEditingBuilder;
