import DraftRow from 'oroorder/js/app/datagrid/rows/draft-row';
import DraftRowModel from './rows/draft-row-model';
import DraftOrderDatagridPlugin from './plugins/draft-order-datagrid-plugin';
import LineItemsDatagridPresentationPlugin from './plugins/line-items-datagrid-presentation-plugin';

export default {
    processDatagridOptions(deferred, options) {
        if (!options.metadata.options.toolbarOptions) {
            options.metadata.options.toolbarOptions = {};
        }

        options.metadata.options.toolbarOptions.disableGridColumns = true;

        if (!options.themeOptions) {
            options.themeOptions = {};
        }

        options.themeOptions = {
            ...options.themeOptions,
            rowView: DraftRow
        };

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }

        options.metadata.plugins.push(
            DraftOrderDatagridPlugin,
            LineItemsDatagridPresentationPlugin
        );

        options.metadata.options.model = DraftRowModel;

        return deferred.resolve();
    },

    init(deferred, options) {
        return deferred.resolve();
    }
};
