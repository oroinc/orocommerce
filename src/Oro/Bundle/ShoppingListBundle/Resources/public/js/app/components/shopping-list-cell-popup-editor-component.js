import _ from 'underscore';
import $ from 'jquery';
import CellPopupEditorComponent from 'orodatagrid/js/app/components/cell-popup-editor-component';
import overlayTool from 'oroui/js/tools/overlay';

const ShoppingListCellPopupEditorComponent = CellPopupEditorComponent.extend({
    constructor: function ShoppingListCellPopupEditorComponent(...args) {
        ShoppingListCellPopupEditorComponent.__super__.constructor.apply(this, args);
    },

    createView() {
        const View = this.options.view;
        const cell = this.options.cell;
        const viewOptions = _.extend({}, this.options.viewOptions, this.getRestrictedOptions(), {
            autoRender: true,
            model: cell.model,
            fieldName: cell.column.get('name'),
            metadata: cell.column.get('metadata')
        });
        if (this.formState) {
            this.updateModel(cell.model, this.oldState);
            this.options.plugin.main.trigger('content:update');
            viewOptions.value = this.formState;
        }

        const viewInstance = this.view = new View(viewOptions);

        viewInstance.$el.addClass('inline-editor-wrapper');

        const overlayOptions = $.extend(true, {}, this.OVERLAY_TOOL_DEFAULTS, {
            insertInto: cell.$el
        });

        overlayTool.createOverlay(viewInstance.$el, overlayOptions);
        viewInstance.trigger('change:visibility');
    },

    saveCurrentCell() {
        return this.options.plugin.saveItems(this);
    },

    isDataValid() {
        return this.view.isValid();
    },

    beforeSaveHook() {
        const modelUpdateData = this.view.getModelUpdateData();
        const cell = this.options.cell;
        this.formState = this.view.getFormState();

        this.oldState = _.pick(cell.model.toJSON(), _.keys(modelUpdateData));
        this.updateModel(cell.model, modelUpdateData);
        this.options.plugin.main.trigger('content:update');
        cell.model.toggleLoadingOverlay(true);

        return this;
    },

    getServerUpdateData() {
        return this.view.getServerUpdateData();
    },

    getModel() {
        if (!this.options.cell.model) {
            return new Error(`model for cell '${this.options.cell.cid}' not exists yet`);
        }
        return this.options.cell.model;
    },

    toggleLoadingOverlay(state) {
        if (!this.options.cell.model) {
            return new Error(`model for cell '${this.options.cell.cid}' not exists yet`);
        }
        this.options.cell.model.toggleLoadingOverlay(state);
    },

    flashRowHighlight(type) {
        if (!this.options.cell.model) {
            return new Error(`model for cell '${this.options.cell.cid}' not exists yet`);
        }
        this.options.cell.model.flashRowHighlight(type);
    },

    cancelAndDispose() {
        this.exitEditMode(true);
    }
});

export default ShoppingListCellPopupEditorComponent;
