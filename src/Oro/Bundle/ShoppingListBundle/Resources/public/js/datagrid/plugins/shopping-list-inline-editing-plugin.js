import _ from 'underscore';
import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import InlineEditingPlugin from 'orodatagrid/js/app/plugins/grid/inline-editing-plugin';
import updateAllBtnTpl from 'tpl-loader!oroshoppinglist/templates/editor/shoppinglist-update-all-btn.html';
import tools from 'oroui/js/tools';
import __ from 'orotranslation/js/translator';
import BaseComponent from 'oroui/js/app/components/base/component';
import NumberFormatter from 'orolocale/js/formatter/number';

/**
 * Recursive resolve query objects
 * @param obj
 * @returns {{}}
 */
function parseQueryObj(obj) {
    return _.mapObject(obj, value => {
        if (typeof value === 'object') {
            value = parseQueryObj(value);
        }

        try {
            value = JSON.parse(value);
        } catch (e) {}

        return value;
    });
}

const ShoppingListInlineEditingPlugin = InlineEditingPlugin.extend({
    componentsToSend: [],

    $updateAllButton: $(updateAllBtnTpl()),

    massUpdate: true,

    /**
     * @inheritdoc
     */
    modalOptions: {
        ...InlineEditingPlugin.prototype.modalOptions,
        okText: __('Yes')
    },

    constructor: function ShoppingListInlineEditingPlugin(...args) {
        ShoppingListInlineEditingPlugin.__super__.constructor.apply(this, args);
    },

    initialize: function(main, options) {
        const {metadata} = options;

        if (metadata.options.mass_update !== void 0) {
            this.massUpdate = metadata.options.mass_update;
        }

        ShoppingListInlineEditingPlugin.__super__.initialize.call(this, main, options);
    },

    enable() {
        ShoppingListInlineEditingPlugin.__super__.enable.call(this);

        this.$updateAllButton.on(`click${this.eventNamespace()}`, this.saveItems.bind(this));

        this.listenToOnce(this.main, 'rendered', this.toggleUpdateAll);
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

    /**
     * Overwrite some methods for cell prototype
     * @param {Constructor} Cell
     * @returns {Constructor}
     */
    cellPatcher(Cell) {
        const PatchedCell = ShoppingListInlineEditingPlugin.__super__.cellPatcher.call(this, Cell);
        const inlineEditingPlugin = this;

        return PatchedCell.extend({
            delayedIconRender() {},
            enterEditModeIfNeeded(e) {
                if (this.isEditable()) {
                    inlineEditingPlugin.enterEditMode(this, e);
                }
                e.preventDefault();
                e.stopPropagation();
            }
        });
    },

    isEditable(cell) {
        if (cell.model && cell.model.get('isConfigurable') || cell.disableEditing) {
            return false;
        }

        return ShoppingListInlineEditingPlugin.__super__.isEditable.call(this, cell);
    },

    onChangeCollection() {
        this.toggleUpdateAll();
    },

    toggleUpdateAll() {
        if (!this.massUpdate) {
            return;
        }

        if (!this.main.$el.find('.grid-header-cell-quantity [data-role="update-all"]').length) {
            this.main.$el.find('.grid-header-cell-quantity').append(this.$updateAllButton);
        }

        if (this.hasChanges()) {
            this.$updateAllButton
                .css('visibility', 'visible')
                .attr({
                    'disabled': null,
                    'aria-hidden': null
                });
        } else {
            this.$updateAllButton
                .css('visibility', 'hidden')
                .attr({
                    'disabled': true,
                    'aria-hidden': true
                });
        }
    },

    saveItems(component, routeParams = {}) {
        let componentsToSend = [];
        if (component instanceof BaseComponent && component.isChanged()) {
            componentsToSend = [component];
            this.cancelEditModeIfMerging(component);
        } else {
            componentsToSend = this.activeEditorComponents.filter(component => component.isChanged());
        }

        componentsToSend = componentsToSend.filter(component => component.isDataValid());

        if (!componentsToSend.length) {
            return;
        }

        const sendData = {
            data: componentsToSend.map(component => component.getServerUpdateData()),
            fetchData: _.extend(this.getGridFetchData(), {
                appearanceType: this.main.collection.state.appearanceType
            }),
            gridName: this.options.gridName
        };

        componentsToSend.forEach(component => component.beforeSaveHook());
        const savePromise = this.saveApiAccessor.send({
            id: this.options.metadata.gridParams.shopping_list_id,
            _wid: tools.createRandomUUID(),
            ...routeParams
        }, sendData);

        const sendModels = componentsToSend.map(component => component.getModel());

        savePromise.done(this.onSaveSuccess.bind(this, sendModels.slice()))
            .fail(this.onSaveError.bind(this, sendModels.slice()))
            .always(this.onSaveComplete.bind(this));

        _.invoke(componentsToSend, 'exitEditMode', true);
        return savePromise;
    },

    onSaveSuccess(models, response) {
        this.main.collection.set(response, {
            uniqueOnly: true,
            parse: true,
            toggleLoading: false,
            alreadySynced: this.allNewDataSynced(response)
        });

        models
            .filter(({id}) => this.main.collection.get(id))
            .forEach(model => {
                const errors = model.get('errors') || [];
                model.flashRowHighlight(errors.length ? 'error' : 'success');
            });
    },

    onSaveError(models) {
        _.invoke(models, 'flashRowHighlight', 'error');

        models.forEach(model => {
            model.toggleLoadingOverlay(false);
        });
    },

    onSaveComplete() {
        mediator.trigger('shopping-list:refresh');

        this.toggleUpdateAll();
    },

    /**
     * Get current grid GET data
     * Unpack to query string and parse string
     * @returns {Object}
     */
    getGridFetchData() {
        return parseQueryObj(tools.unpackFromQueryString(
            tools.packToQueryString(this.main.collection.getFetchData())
        ));
    },

    enterEditMode(cell, event) {
        cell.trigger('before-enter-edit-mode');

        const existingEditorComponent = this.getOpenedEditor(cell);
        if (existingEditorComponent) {
            return;
        }

        this.main.ensureCellIsVisible(cell);

        const editor = this.getCellEditorOptions(cell);
        editor.viewOptions.className = this.buildClassNames(editor, cell).join(' ');

        if (cell.el.contains(document.activeElement) && $(document.activeElement).is('div.input')) {
            const formattedValue = document.activeElement.innerText
                .trim()
                .substring(0, window.getSelection().focusOffset);

            editor.viewOptions.cursorOffset = formattedValue
                ? NumberFormatter.unformat(formattedValue).toString().length
                : 0;
        }

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

        editorComponent.view.component = editorComponent;

        this.activeEditorComponents.push(editorComponent);
        this.listenTo(editorComponent, 'dispose', this.onDisposeEditor.bind(this, editorComponent));
        this.listenTo(editorComponent, 'cancelAction', () => {
            this.toggleUpdateAll();
        });

        editorComponent.view.scrollIntoView();
        editorComponent.view.focus(event);
    },

    /**
     * While a line item is going to save and there might be another line item in edit mode might merge by it,
     * Cancel its edit mode.
     */
    cancelEditModeIfMerging(saveComponent) {
        this.activeEditorComponents.map(component => {
            const itemModel = component.view.model;
            if (saveComponent.cid !== component.cid &&
                saveComponent.view.model.attributes.sku === itemModel.attributes.sku &&
                saveComponent.view.getUnitCode() === itemModel.attributes.unit) {
                component.exitEditMode(true);
            }
        });
    },

    allNewDataSynced(response) {
        if (!response.data) {
            return true;
        }

        const intersectCollection = [];
        response.data.forEach(lineItem => {
            if (this.main.collection.get(lineItem.id)) {
                intersectCollection.push(lineItem.id);
            }
        });

        return intersectCollection.length > 0;
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
