import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppingListHtmlTemplateCell = HtmlTemplateCell.extend({
    constructor: function ShoppingListHtmlTemplateCell(options) {
        ShoppingListHtmlTemplateCell.__super__.constructor.call(this, options);
    },

    _attributes() {
        const attrs = {};

        if (this.model.get('isConfigurable') && !this.model.get(this.column.get('name'))) {
            attrs['aria-label'] = null;
            attrs['aria-colindex'] = null;
        }

        return attrs;
    }
});

export default ShoppingListHtmlTemplateCell;
