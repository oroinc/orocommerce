import ActionLauncher from 'orodatagrid/js/datagrid/action-launcher';
import template from 'tpl-loader!oroshoppinglist/templates/datagrid/launchers/select-all-items-launcher.html';

const SelectAllItemsLauncher = ActionLauncher.extend({
    template,

    events: {
        'click [data-select]:checkbox': 'onCheckboxClick'
    },

    constructor: function SelectAllItemsLauncher(options) {
        SelectAllItemsLauncher.__super__.constructor.call(this, options);
    },

    initialize(options) {
        this.selectState = options.action.datagrid.selectState;
        this.collection = options.action.datagrid.collection;
        this.listenTo(this.selectState, 'change', this.onChangeSelection);
        this.themeOptions = options.action.datagrid.themeOptions || {};
        SelectAllItemsLauncher.__super__.initialize.call(this, options);
    },

    onCheckboxClick(e) {
        if (this.selectState.get('inset') && this.selectState.isEmpty()) {
            this.collection.trigger(this.themeOptions.selectCellEventActionName ?? 'backgrid:selectAll');
        } else {
            this.collection.trigger('backgrid:selectNone');
        }
        e.stopPropagation();
    },

    onChangeSelection(selectState) {
        this.$('[data-select]:checkbox').prop({
            indeterminate: !selectState.isEmpty(),
            checked: !selectState.get('inset')
        });
    }
});

export default SelectAllItemsLauncher;
