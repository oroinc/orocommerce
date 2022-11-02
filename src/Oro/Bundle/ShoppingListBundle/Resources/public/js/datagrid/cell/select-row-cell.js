import SelectRowCell from 'oro/datagrid/cell/select-row-cell';
import SelectStateModel from 'orodatagrid/js/datagrid/select-state-model';
import template from 'tpl-loader!oroshoppinglist/templates/datagrid/cell/select-row-cell.html';

const ShoppingListSelectRowCell = SelectRowCell.extend({
    template: template,

    constructor: function ShoppingListSelectRowCell(options) {
        return ShoppingListSelectRowCell.__super__.constructor.call(this, options);
    },

    initialize(options) {
        ShoppingListSelectRowCell.__super__.initialize.call(this, options);

        if (!this.model.isGroup) {
            return;
        }

        this.selectState = new SelectStateModel();
        this.listenTo(this.model.collection, 'backgrid:selected', this.onSomeRowSelect);
    },

    onChange(e) {
        if (this.model.isGroup) {
            const newState = this.$(e.target).prop('checked');
            this.model.subModels()
                .forEach(model => model.trigger('backgrid:select', model, newState));
        } else {
            ShoppingListSelectRowCell.__super__.onChange.call(this, e);
        }
    },

    onSomeRowSelect(model, isSelected) {
        if (this.model.subModels().indexOf(model) === -1) {
            // that's not a model of the group
            return;
        }

        if (isSelected) {
            this.selectState.addRow(model);
        } else {
            this.selectState.removeRow(model);
        }
        this.updateGroupState();
    },

    updateGroupState: function() {
        const isAllSelected = this.selectState.get('rows').length === this.model.get('ids').length;
        this.$checkbox.prop({
            indeterminate: !this.selectState.isEmpty() && !isAllSelected,
            checked: isAllSelected
        });
    }
});

export default ShoppingListSelectRowCell;
