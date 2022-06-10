class TableModify {
    constructor(component) {
        this.component = component;
        this.em = this.component.em;
    }

    static getTableComponent(model) {
        if (!TableModify.tableTypes.includes(model.get('type'))) {
            return false;
        }

        return model.closestType(this.components.get('type'));
    }

    getRows(space = 'tbody') {
        const container = this.component.findType(space)[0];
        const rows = container ? container.components() : [];

        return {
            getAll() {
                return rows.models || [];
            },
            getByIndex(index) {
                return rows.at(index);
            },
            remove(index) {
                const toRemove = rows.at(index);
                toRemove.remove();

                return rows;
            },
            length: rows.length
        };
    }

    getAllRows(context) {
        const rows = this.getRows().getAll() || [];
        const hRows = this.getRows('thead').getAll() || [];
        const fRows = this.getRows('tfoot').getAll() || [];

        if (context === 'thead') {
            return hRows;
        }

        if (context === 'tbody') {
            return rows;
        }

        if (context === 'tfoot') {
            return fRows;
        }

        return [...hRows, ...rows, ...fRows];
    }

    getColumns(context) {
        const columns = this.getAllRows(context).reduce((acc, row) => {
            const cells = row.components();
            if (!acc.length) {
                acc = new Array(cells.length).fill([]);
            }

            if (acc.length < cells.length) {
                acc = acc.concat(new Array(cells.length - acc.length).fill([]));
            }

            cells.forEach((cell, index) => acc[index] = [
                ...acc[index],
                cell
            ]);

            return acc;
        }, []);

        return {
            getAll() {
                return columns;
            },
            getByIndex(columnIndex) {
                return columns[columnIndex];
            },
            remove(columnIndex) {
                const column = this.getByIndex(columnIndex);
                column.forEach(cell => cell.remove());
            },
            length: columns.length
        };
    }

    modify(command, {selected}) {
        let rowIndex;
        let columnIndex;
        let context;

        if (['tbody', 'thead', 'table', this.component.get('type')].includes(selected.get('type'))) {
            rowIndex = 0;
            columnIndex = 0;
            context = selected.get('type') === 'thead' ? 'thead' : 'tbody';
        } else {
            const parent = selected.is('cell') ? selected.parent() : selected;
            rowIndex = parent.collection.indexOf(parent);
            columnIndex = selected.collection.indexOf(selected);
            context = this.getContext(selected);
        }

        switch (command) {
            case 'insert-row-after':
                this.insertRow(rowIndex + 1, columnIndex, context);
                break;
            case 'insert-row-before':
                this.insertRow(rowIndex, columnIndex, context);
                break;
            case 'insert-column-after':
                this.insertColumn(columnIndex + 1, rowIndex, context);
                break;
            case 'insert-column-before':
                this.insertColumn(columnIndex, rowIndex, context);
                break;
            case 'delete-row':
                this.deleteRow(rowIndex, columnIndex, context);
                break;
            case 'delete-column':
                this.deleteColumn(rowIndex, columnIndex, context);
                break;
            case 'select-parent':
                this.em.get('Editor').runCommand('core:component-exit', {
                    force: 1
                });
                break;
            case 'delete-table':
                this.deleteTable();
                break;
        }
    }

    getContext(model) {
        if (model.closestType('thead')) {
            return 'thead';
        }

        if (model.closestType('tbody') || model.get('type') === this.component.get('type') || model.is('table')) {
            return 'tbody';
        }

        if (model.closestType('tfoot')) {
            return 'tfoot';
        }

        return 'tbody';
    }

    insertRow(index, columnIndex, context) {
        const container = this.component.findType(context)[0];
        const columnCount = this.getColumns().length || 1;
        const opts = {};

        if (index < container.components().length) {
            opts.at = index;
        }

        container.append([{
            type: 'row',
            tagName: 'tr',
            components: new Array(columnCount).fill({
                type: 'cell',
                tagName: context === 'thead' ? 'th' : 'td'
            })
        }], opts);

        this.em.trigger('change:canvasOffset');
    }

    insertColumn(index) {
        const rows = this.getRows();
        const hRows = this.getRows('thead');
        const fRows = this.getRows('tfoot');

        rows.getAll().forEach(row => row.append(
            [{
                type: 'cell'
            }], {
                at: index
            }
        ));

        if (hRows.length) {
            hRows.getAll().forEach(row => row.append(
                [{
                    tagName: 'th',
                    type: 'cell'
                }], {
                    at: index
                }
            ));
        }

        if (fRows.length) {
            fRows.getAll().forEach(row => row.append(
                [{
                    type: 'cell'
                }], {
                    at: index
                }
            ));
        }

        this.em.trigger('change:canvasOffset');
    }

    deleteRow(rowIndex, columnIndex, context) {
        const rows = this.getRows(context);

        this.em.get('Commands').stop('table-edit');

        const toSelectRow = rows.getByIndex([rowIndex === 0 ? rowIndex + 1 : rowIndex - 1]);
        if (toSelectRow) {
            const toSelect = toSelectRow.components().at(columnIndex);
            if (toSelect) {
                this.em.get('Editor').select(toSelect);
            }
        }

        rows.remove(rowIndex);

        if (context !== 'tbody' && !this.getRows(context).length) {
            this.getTableContext(context).remove();
        }

        if (!this.getAllRows().length) {
            this.deleteTable();
        } else {
            if (!toSelectRow) {
                setTimeout(() => {
                    if (!this.component.isRemoved) {
                        this.em.get('Editor').select(this.component);
                    }
                });
            }
        }

        this.em.trigger('change:canvasOffset');
    }

    deleteColumn(rowIndex, columnIndex, context) {
        const columns = this.getColumns();

        this.em.get('Commands').stop('table-edit');

        const toSelectColumn = this.getColumns(context)
            .getByIndex([columnIndex === 0 ? columnIndex + 1 : columnIndex - 1]);

        if (toSelectColumn) {
            const toSelect = toSelectColumn[rowIndex];
            if (toSelect) {
                this.em.get('Editor').select(toSelect);
            }
        }

        columns.remove(columnIndex);

        if (!this.getColumns().length) {
            this.deleteTable();
        }

        this.em.trigger('change:canvasOffset');
    }

    deleteTable() {
        this.em.get('Commands').stop('table-edit');
        this.em.getSelectedAll().forEach(selected => this.em.get('Editor').selectRemove(selected));
        this.component.isRemoved = true;

        if (this.component.referrer) {
            this.component.referrer.remove();
        } else {
            this.component.remove();
        }
    }

    isHead(model) {
        return model.closestType('thead');
    }

    getTableContext(context) {
        return this.component.findType(context)[0];
    }
}

TableModify.tableTypes = ['cell', 'row', 'thead', 'tbody', 'tfoot', 'table'];

export default TableModify;
