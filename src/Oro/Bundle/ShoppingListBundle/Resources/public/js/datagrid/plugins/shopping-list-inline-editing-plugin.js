import _ from 'underscore';
import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import InlineEditingPlugin from 'orodatagrid/js/app/plugins/grid/inline-editing-plugin';
import updateAllBtnTpl from 'tpl-loader!oroshoppinglist/templates/editor/shoppinglist-update-all-btn.html';
import tools from 'oroui/js/tools';

const ShoppingListInlineEditingPlugin = InlineEditingPlugin.extend({
    componentsToSend: [],

    $updateAllButton: $(updateAllBtnTpl()),

    constructor: function ShoppingListInlineEditingPlugin(...args) {
        ShoppingListInlineEditingPlugin.__super__.constructor.apply(this, args);
    },

    enable() {
        ShoppingListInlineEditingPlugin.__super__.enable.call(this);

        this.$updateAllButton.on(`click${this.eventNamespace()}`, this.updateAll.bind(this));

        this.listenTo(this.main.collection, {
            'change:_state': this.onChangeCollection
        });
    },

    removeActiveEditorComponents: function() {
        const activeEditorComponents = this.activeEditorComponents.slice();
        for (let i = 0; i < activeEditorComponents.length; i++) {
            activeEditorComponents[i].exitEditMode(true);
        }
        this.activeEditorComponents = [];

        this.toggleUpdateAll();
    },

    patchCellConstructor(column) {
        ShoppingListInlineEditingPlugin.__super__.patchCellConstructor.call(this, column);

        const cell = column.get('cell').extend({
            delayedIconRender() {}
        });

        column.set('cell', cell);
    },

    isEditable(cell) {
        if (cell.model && cell.model.get('isConfigurable')) {
            return false;
        }

        return ShoppingListInlineEditingPlugin.__super__.isEditable.call(this, cell);
    },

    onChangeCollection() {
        this.toggleUpdateAll();
    },

    toggleUpdateAll() {
        if (this.hasChanges()) {
            if (!this.main.$el.find('.grid-header-cell-quantity .btn').length) {
                this.main.$el.find('.grid-header-cell-quantity').append(this.$updateAllButton);
            }

            this.$updateAllButton.toggle(true);
        } else {
            this.$updateAllButton.toggle(false);
        }
    },

    updateAll() {
        this.componentsToSend = this.activeEditorComponents.filter(component => component.isChanged());
        const data = this.componentsToSend.map(component => component.view.getServerUpdateData());
        _.invoke(this.componentsToSend, 'toggleLoadingOverlay', true);

        const sendData = {
            data
        };

        const savePromise = this.saveApiAccessor.send({
            id: this.options.metadata.gridParams.shopping_list_id,
            _wid: tools.createRandomUUID()
        }, sendData);

        savePromise.done(_.bind(this.onSaveSuccess, this))
            .fail(_.bind(this.onSaveError, this))
            .always(_.bind(this.onSaveComplete, this));

        return savePromise;
    },

    onSaveSuccess(response) {
        const {totalRecords, hideToolbar} = this.main.collection.state;

        this.main.collection.set({
            data: response.data.items,
            options: {
                totalRecords,
                hideToolbar,
                ...response.options
            }
        }, {
            uniqueOnly: true,
            remove: false,
            parse: true
        });

        _.invoke(this.componentsToSend, 'flashRowHighlight', 'success');
    },

    onSaveError() {
        _.invoke(this.componentsToSend, 'flashRowHighlight', 'error');
    },

    onSaveComplete() {
        this.componentsToSend.forEach(component => {
            component.toggleLoadingOverlay(false);
            component.exitEditMode(true);
        });
        mediator.trigger('shopping-list:refresh');

        this.toggleUpdateAll();
        this.componentsToSend = [];
    },

    enterEditMode(cell, fromPreviousCell) {
        const existingEditorComponent = this.getOpenedEditor(cell);
        if (existingEditorComponent) {
            return;
        }

        this.main.ensureCellIsVisible(cell);

        const editor = this.getCellEditorOptions(cell);
        editor.viewOptions.className = this.buildClassNames(editor, cell).join(' ');

        const CellEditorComponent = editor.component;
        const CellEditorView = editor.view;

        if (!CellEditorView) {
            throw new Error('Editor view in not available for `' + cell.column.get('name') + '` column');
        }

        const editorComponent = new CellEditorComponent(_.extend({}, editor.component_options, {
            cell: cell,
            view: CellEditorView,
            viewOptions: editor.viewOptions,
            save_api_accessor: editor.save_api_accessor,
            grid: this.main,
            plugin: this
        }));

        this.activeEditorComponents.push(editorComponent);
        this.listenTo(editorComponent, 'dispose', this.onDisposeEditor.bind(this, editorComponent));
        this.listenTo(editorComponent, 'cancelAction', () => {
            this.toggleUpdateAll();
        });

        editorComponent.view.scrollIntoView();
        editorComponent.view.focus();
    },

    onDisposeEditor(instance) {
        if (this.disposed) {
            return;
        }

        const index = this.activeEditorComponents.indexOf(instance);
        if (index !== -1) {
            this.activeEditorComponents.splice(index, 1);
        }
    }
});

export default ShoppingListInlineEditingPlugin;
