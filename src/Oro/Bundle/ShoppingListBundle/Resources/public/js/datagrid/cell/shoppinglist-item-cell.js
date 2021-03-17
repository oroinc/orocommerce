import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppingListItemCell = HtmlTemplateCell.extend({
    constructor: function ShoppingListItemCell(options) {
        ShoppingListItemCell.__super__.constructor.call(this, options);
        this.listenTo(this.model, 'change', this.render);
    },

    render() {
        const templateKey = this.model.get('isMessage') ? 'message' : 'default';
        const template = this.getTemplateFunction(templateKey);
        const html = template(this.getTemplateData());

        if (this._html !== html) { // prevents from unnecessary HTML update
            this._html = html;
            this.$el
                .trigger('content:remove')
                .html(html)
                .trigger('content:changed');
            this.appendEditNotesAction();
        }

        return this;
    },

    appendEditNotesAction() {
        const $note = this.$('[data-role=notes]');
        const actionsColumn = this.column.collection.find(model => model.get('actions'));

        if (!$note.length || !actionsColumn) {
            return;
        }

        const EditNotesAction = actionsColumn.get('actions').edit_notes;
        this.editNotesAction = new EditNotesAction({
            model: this.model,
            datagrid: actionsColumn.get('datagrid')
        });

        const launcher = this.editNotesAction.createLauncher({
            launcherMode: 'icon-only',
            className: 'grid-line-items__edit-notes'
        });
        $note.after(launcher.render().$el);
    }
});

export default ShoppingListItemCell;
