import AbstractAction from 'oro/datagrid/action/abstract-action';
import SelectAllItemsLauncher from 'oroshoppinglist/js/datagrid/launchers/select-all-items-laucher';

const SelectAllItemsAction = AbstractAction.extend({
    launcher: SelectAllItemsLauncher,

    constructor: function SelectAllItemsAction(options) {
        SelectAllItemsAction.__super__.constructor.call(this, options);
    },

    initialize(options) {
        SelectAllItemsAction.__super__.initialize.call(this, options);

        this.listenTo(this.datagrid.collection, 'change reset', this.onCollectionChange);
    },

    onCollectionChange() {
        this.launcherInstance.toggleVisibility(!this.datagrid.collection.length);
    },

    execute() {}
});

export default SelectAllItemsAction;
