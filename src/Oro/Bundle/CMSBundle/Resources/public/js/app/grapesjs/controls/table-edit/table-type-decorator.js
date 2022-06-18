export default {
    init() {
        this.bindModelEvents();
    },

    bindModelEvents() {
        this.listenTo(this, 'model:selected', this.onSelected);
        this.listenTo(this, 'model:deselected', this.onDeselected);
    },

    getTable() {
        if (this.get('type') === 'table') {
            return this;
        }

        const table = this.closestType('table');
        if (table) {
            return table;
        }

        return false;
    },

    onSelected() {
        const commands = this.em.get('Commands');
        const table = this.getTable();

        if (table && commands.has('table-edit')) {
            if (commands.isActive('table-edit')) {
                commands.stop('table-edit');
            }
            commands.run('table-edit', table);
        }
    },

    onDeselected() {
        const commands = this.em.get('Commands');

        if (commands.has('table-edit') && commands.isActive('table-edit')) {
            commands.stop('table-edit');
        }
    }
};
