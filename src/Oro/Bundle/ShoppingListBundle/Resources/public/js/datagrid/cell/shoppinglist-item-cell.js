import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppingListItemCell = HtmlTemplateCell.extend({
    constructor: function ShoppingListItemCell(options) {
        ShoppingListItemCell.__super__.constructor.call(this, options);
        this.listenTo(this.model, 'change:notes', this.render);
    },

    render() {
        const template = this.getTemplateFunction();
        this.$el.html(template(this.getTemplateData()));

        this.appendEditNotesAction();

        return this;
    },

    appendEditNotesAction() {
        const $note = this.$('[data-role=notes]');
        if (!$note.length) {
            return;
        }

        const actionsColumn = this.column.collection.find(model => model.get('actions'));
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
