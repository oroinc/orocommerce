import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppingListHtmlTemplateProductKitCell = HtmlTemplateCell.extend({
    constructor: function ShoppingListHtmlTemplateProductKitCell(...args) {
        ShoppingListHtmlTemplateProductKitCell.__super__.constructor.apply(this, args);
    },

    _attributes() {
        if (this.column.get('name') === 'subtotal') {
            return {
                'aria-label': null,
                'data-blank-content': null,
                'aria-colindex': null
            };
        }
    }
});

export default ShoppingListHtmlTemplateProductKitCell;
