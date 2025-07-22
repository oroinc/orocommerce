import ShoppingListHtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppingListInventoryStatusCell = ShoppingListHtmlTemplateCell.extend({
    constructor: function ShoppingListInventoryStatusCell(options) {
        ShoppingListInventoryStatusCell.__super__.constructor.call(this, options);
        this.listenTo(this.model, 'change', this.render);
    }
});

export default ShoppingListInventoryStatusCell;
