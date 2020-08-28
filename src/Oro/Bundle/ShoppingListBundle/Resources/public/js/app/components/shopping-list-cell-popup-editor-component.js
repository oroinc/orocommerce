import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import CellPopupEditorComponent from 'orodatagrid/js/app/components/cell-popup-editor-component';
import overlayTool from 'oroui/js/tools/overlay';
import tools from 'oroui/js/tools';

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
            this.errorHolderView.render();
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
        if (!this.view.isChanged()) {
            this.exitEditMode(true);
            return true;
        }
        if (!this.view.isValid()) {
            return false;
        }

        const cell = this.options.cell;
        const modelUpdateData = this.view.getModelUpdateData();
        const sendData = this.getServerUpdateData();
        this.formState = this.view.getFormState();

        cell.model.toggleLoadingOverlay(true);

        this.oldState = _.pick(cell.model.toJSON(), _.keys(modelUpdateData));
        this.exitEditMode();

        this.updateModel(cell.model, modelUpdateData);
        this.errorHolderView.render();
        this.options.plugin.main.trigger('content:update');

        let savePromise = this.options.save_api_accessor.send({
            id: this.options.grid.metadata.gridParams.shopping_list_id,
            _wid: tools.createRandomUUID()
        }, sendData, {}, {
            errorHandlerMessage: false,
            preventWindowUnload: __('oro.form.inlineEditing.inline_edits')
        });

        if (this.constructor.processSavePromise) {
            savePromise = this.constructor.processSavePromise(savePromise, cell.column.get('metadata'));
        }
        if (this.options.view.processSavePromise) {
            savePromise = this.options.view.processSavePromise(savePromise, cell.column.get('metadata'));
        }

        savePromise.done(_.bind(this.onSaveSuccess, this, cell))
            .fail(_.bind(this.onSaveError, this, cell))
            .always(_.bind(this.onSaveComplete, this, cell));

        return savePromise;
    },

    getServerUpdateData() {
        return {
            data: [this.view.getServerUpdateData()]
        };
    },

    onSaveComplete(cell) {
        cell.model.toggleLoadingOverlay(false);

        mediator.trigger('shopping-list:refresh');
    },

    onSaveSuccess(cell, response) {
        const {totalRecords, hideToolbar} = cell.model.collection.state;

        if (response && response.data.items.length) {
            cell.model.collection.set({
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

            cell.model.flashRowHighlight(cell.model.get('success') ? 'success' : 'error');
        }

        delete this.oldState;
        delete this.formState;
        this.errorHolderView.setErrorMessages({});
        this.exitEditMode(true);
    },

    onSaveError(cell, jqXHR) {
        cell.model.flashRowErrorHighlight();
    },

    toggleLoadingOverlay(state) {
        this.options.cell.model.toggleLoadingOverlay(state);
    },

    flashRowHighlight(type) {
        this.options.cell.model.flashRowHighlight(type);
    }
});

export default ShoppingListCellPopupEditorComponent;
