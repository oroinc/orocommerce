import ShoppingListEditItemModel from 'oroshoppinglist/js/datagrid/model-edit';

const shoppingListEditingBuilder = {
    processDatagridOptions(deferred, options) {
        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }

        options.metadata.inline_editing = {
            ...options.metadata.inline_editing,
            default_editors: 'oroshoppinglist/js/inline-editing/shopping-list-editors'
        };

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
