import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppingListItemCell = HtmlTemplateCell.extend({
    constructor: function ShoppingListItemCell(options) {
        ShoppingListItemCell.__super__.constructor.call(this, options);
    },

    render: function() {
        const template = this.getTemplateFunction();
        this.$el.html(template(this.getTemplateData()));
        const $note = this.$('[data-role=note]');

        if ($note) {
            const actionsColumn = this.column.collection.find(model => model.get('actions'));
            const EditNoteAction = actionsColumn.get('actions').edit_notes;
            this.editNoteAction = new EditNoteAction({
                model: this.model,
                datagrid: actionsColumn.get('datagrid')
            });

            const launcher = this.editNoteAction.createLauncher({
                launcherMode: 'icon-only',
                className: 'grid-line-items__edit-note'
            });
            $note.after(launcher.render().$el);
        }

        return this;
    }
});

export default ShoppingListItemCell;
