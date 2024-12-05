import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppingListSkuCell = HtmlTemplateCell.extend({
    constructor: function ShoppingListSkuCell(options) {
        ShoppingListSkuCell.__super__.constructor.call(this, options);
        this.listenTo(this.model, 'change', this.render);
    },

    _attributes() {
        const attrs = {};

        if (this.model.get('isMessage')) {
            attrs['id'] = this.model.get('id');
        } else {
            attrs['aria-describedby'] = this.model.get('messageModelId');
        }

        return attrs;
    },

    render() {
        const templateKey = this.model.get('_templateKey') || 'default';
        const template = this.getTemplateFunction(templateKey);
        const html = template(this.getTemplateData());

        if (this._html !== html) { // prevents from unnecessary HTML update
            this._html = html;
            this.$el
                .trigger('content:remove')
                .html(html)
                .trigger('content:changed');
        }

        return this;
    }
});

export default ShoppingListSkuCell;
