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
    init: (deferred, options) => {
        options.gridPromise.done(grid => {
            grid.collection.on('change:errors', (model, collection, options) => {
                console.log(model)
            });
        });

        return deferred.resolve();
    }
};

export default shoppingListEditingBuilder;
