import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import routing from 'routing';
import pageStateChecker from 'oronavigation/js/app/services/page-state-checker';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import Modal from 'oroui/js/modal';

const DraftOrderDatagridPlugin = BasePlugin.extend({
    /**
     * Options for bootstrap modal
     */
    modalOptions: {
        title: __('oro.datagrid.inline_editing.refresh_confirm_modal.title'),
        content: __('oro.order.leave_page_with_unsaved_data_confirm'),
        okText: __('Ok, got it'),
        className: 'modal modal-primary',
        cancelText: __('Cancel')
    },

    constructor: function DraftOrderDatagridPlugin(...args) {
        this.hasChanges = this.hasChanges.bind(this);
        DraftOrderDatagridPlugin.__super__.constructor.apply(this, args);
    },

    enable() {
        this.main.$el.addClass('grid-editable');

        // Expose batch content provider on the collection so DraftRow can access it
        this.main.collection.batchContentProvider = this;
        this._pendingRequests = {};
        this._executeBatchRequest = _.debounce(this._executeBatchRequestImpl.bind(this), 0);

        this.listenTo(this.main.collection, {
            beforeFetch: this.beforeGridCollectionFetch
        });

        this.listenTo(mediator, {
            'page:beforeRedirectTo': this.beforeRedirectTo,
            'page:beforeRefresh': this.beforeRefresh,
            'before:submitPage': this.beforeRefresh,
            'datagrid:hightlightRow': this.beforeNavigation,
            [`datagrid:doRefresh:orderDraftGrid:${this.main.collection.inputName}`]: this._onDatagridRefresh
        });

        DraftOrderDatagridPlugin.__super__.enable.call(this);
    },

    fullRefreshCollection(updatedIds) {
        if (Array.isArray(updatedIds) && updatedIds.length) {
            this.listenToOnce(this.main.collection, 'reset', () =>
                mediator.trigger('datagrid:highlightUpdated:' + this.main.name, ...updatedIds)
            );
        }

        mediator.trigger('datagrid:doRefresh:' + this.main.name);
        mediator.trigger('entry-point:order:trigger');
    },

    collectionSync(updatedIds) {
        const collection = this.main.collection;

        const idAttr = collection.model.prototype.idAttribute || 'id';

        // Build request data with current grid state (sorting, filters, pagination)
        const data = collection.getFetchData();
        data[collection.inputName] = collection.urlParams;

        collection.sync('read', collection, {
            data,
            success: collectionData => {
                const responseOptions = collection._parseResponseOptions(collectionData);
                const newCollectionData = collectionData.data;

                const newDataById = {};
                newCollectionData.forEach(item => {
                    newDataById[String(item[idAttr])] = item;
                });

                // Remove models that no longer exist in the response
                const modelsToRemove = collection.models.filter(
                    model => !newDataById[String(model.id)]
                );

                if (modelsToRemove.length) {
                    collection.remove(modelsToRemove, {
                        recountTotalRecords: false,
                        silent: true
                    });
                }

                if (collection.length === 0) {
                    return collection.reset(newCollectionData, {
                        totals: responseOptions.totals
                    });
                }

                // Update existing or add new models
                const updatedModels = [];

                newCollectionData.forEach(newData => {
                    const id = String(newData[idAttr]);
                    const existingModel = collection.get(id);

                    if (existingModel) {
                        if (this._isModelChanged(existingModel, newData)) {
                            existingModel.set(
                                {...newData, editMode: false},
                                {silent: true}
                            );
                            updatedModels.push(existingModel);
                        }
                    } else {
                        const added = collection.add(newData, {silent: true});
                        updatedModels.push(added);
                    }
                });

                // Reorder collection.models to match server response order
                collection.models = newCollectionData.map(
                    item => collection.get(String(item[idAttr]))
                ).filter(Boolean);

                collection.trigger('sort', collection);

                // Re-render only the rows whose data actually changed
                updatedModels.forEach(model => {
                    const rowView = this.main.body.rows
                        .find(row => row.model === model);

                    if (rowView) {
                        for (const key of Object.keys(rowView.getItemViews())) {
                            rowView.removeSubview(`itemView:${key}`);
                        }
                        rowView.render();
                    }
                });

                this.highlightUpdatedItems(collection, updatedIds);

                if (responseOptions) {
                    collection.updateState({
                        totalRecords: responseOptions.totalRecords
                    });
                }
            }
        });
    },

    highlightUpdatedItems(collection, updatedIds) {
        if (Array.isArray(updatedIds)) {
            updatedIds.forEach(id => {
                const model = collection.get(String(id));
                if (model) {
                    model.set('isUpdated', true);

                    if (model.get('editMode') === true) {
                        model.set('editMode', false);
                    }
                }
            });
        }
    },

    _onDatagridRefresh({updatedIds} = {}) {
        const collection = this.main.collection;

        if (collection.length === 0) {
            return this.fullRefreshCollection(updatedIds);
        }

        this.collectionSync(updatedIds);

        mediator.trigger('entry-point:order:trigger');
    },

    /**
     * Compares existing model attributes with incoming data.
     * Returns true if any attribute value changed.
     *
     * @param {Backbone.Model} model
     * @param {Object} newData
     * @returns {boolean}
     */
    _isModelChanged(model, newData) {
        const attrs = model.attributes;
        return Object.keys(newData).some(key => !_.isEqual(attrs[key], newData[key]));
    },

    confirmNavigation: function() {
        const confirmModal = new Modal(this.modalOptions);
        const deferredConfirmation = $.Deferred();

        deferredConfirmation.always(() => {
            this.stopListening(confirmModal);
        });

        this.listenTo(confirmModal, 'ok', function() {
            deferredConfirmation.resolve();
        });
        this.listenTo(confirmModal, 'cancel close', function() {
            deferredConfirmation.reject(deferredConfirmation.promise(), 'abort');
        });
        // once navigation is confirmed, set changes to be ignored
        deferredConfirmation.then(() => pageStateChecker.ignoreChanges());
        confirmModal.open();

        return deferredConfirmation;
    },

    hasChanges() {
        return this.main.collection.some(model => model.get('fieldChanged') === true);
    },

    /**
     * Registers a line item id to be fetched in the next batch request.
     * Returns a Promise that resolves with the HTML content for that line item.
     * The returned promise has an .abort() method that aborts the underlying XHR.
     *
     * @param {string|number} lineItemId
     * @returns {Promise<string>}
     */
    requestContent(lineItemId) {
        const promise = new Promise((resolve, reject) => {
            this._pendingRequests[String(lineItemId)] = {resolve, reject};
            this._executeBatchRequest();
        });
        promise.abort = () => this.batchXhr?.abort();
        return promise;
    },

    _executeBatchRequestImpl() {
        const pending = this._pendingRequests;
        this._pendingRequests = {};

        const ids = Object.keys(pending);
        if (!ids.length) {
            return;
        }

        this.batchXhr = this.updateWidgetContentBatchGet(ids)
            .then(({lineItems}) => {
                const resolvedIds = {};
                lineItems.forEach(({lineItemId, html}) => {
                    const id = String(lineItemId);
                    pending[id]?.resolve(html);
                    resolvedIds[id] = true;
                });
                // Reject any ids that didn't come back in the response
                Object.entries(pending).forEach(([id, {reject: rej}]) => {
                    if (!resolvedIds[id]) {
                        rej(new Error(`No content returned for line item ${id}`));
                    }
                });
            }).catch(error => {
                Object.values(pending).forEach(({reject: rej}) => rej(error));
            }).always(() => {
                this.batchXhr = null;
            });
    },

    beforeGridCollectionFetch(collection, options) {
        if (this.hasChanges()) {
            const deferredConfirmation = this.confirmNavigation();
            options.waitForPromises.push(deferredConfirmation.promise());
        }
    },

    beforeRefresh(queue) {
        return this.beforeNavigation(queue);
    },

    beforeRedirectTo(queue) {
        return this.beforeNavigation(queue);
    },

    beforeNavigation: function(queue) {
        if (this.hasChanges()) {
            const deferredConfirmation = this.confirmNavigation();
            queue.push(deferredConfirmation.promise());
        }
    },

    updateWidgetContentBatchGet(orderLineItemIds) {
        const url = routing.generate('oro_order_line_item_draft_mass_update', {
            orderId: this.main.metadata.gridParams.order_id,
            orderLineItemIds: orderLineItemIds.join(','),
            orderDraftSessionUuid: this.main.metadata.gridParams.draft_session_uuid
        });

        return $.ajax(url, {
            errorHandlerMessage: __('oro.order.draft_line_items_datagrid.batch_update_content_load_error')
        });
    }
});

export default DraftOrderDatagridPlugin;
