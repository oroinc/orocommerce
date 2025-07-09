import Backbone from 'backbone';
import mediator from 'oroui/js/mediator';
import ShoppingListSelectRowCell from 'oroshoppinglist/js/datagrid/cell/select-row-cell';
import ShoppingListModel from 'oroshoppinglist/js/datagrid/model';

const shoppinglistGridOptionsBuilder = {
    processDatagridOptions(deferred, options) {
        const observer = Object.create(Backbone.Events);
        observer.listenTo(mediator, 'datagrid_create_before', gridOptions => {
            if (gridOptions.metadata === options.metadata) {
                gridOptions.selectRowCell = ShoppingListSelectRowCell;
                observer.stopListening();
            }
        });
        options.gridPromise.fail(() => observer.stopListening());

        options.metadata.options.model = ShoppingListModel;

        if (options.themeOptions.singleUnitMode && !options.themeOptions.singleUnitModeCodeVisible) {
            const unitColumn = options.metadata.columns.find(column => column.name === 'unit');
            if (unitColumn) {
                unitColumn.renderable = false;
            }
        }

        deferred.resolve();
    },

    /**
     * Init() function is required
     */
    init: deferred => deferred.resolve()
};

export default shoppinglistGridOptionsBuilder;
