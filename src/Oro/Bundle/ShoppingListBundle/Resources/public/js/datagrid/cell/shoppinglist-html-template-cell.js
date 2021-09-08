import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';
import ShoppinglistStringCell from 'oro/datagrid/cell/shoppinglist-string-cell';

const ShoppingListHtmlTemplateCell = HtmlTemplateCell.extend({
    attributes: ShoppinglistStringCell.prototype.attributes,

    constructor: function ShoppingListHtmlTemplateCell(options) {
        ShoppingListHtmlTemplateCell.__super__.constructor.call(this, options);
    }
});

export default ShoppingListHtmlTemplateCell;
