import $ from 'jquery';
import _ from 'underscore';
import routing from 'routing';
import QuickAddModel from 'oroproduct/js/app/models/quick-add-model';
import BaseCollection from 'oroui/js/app/models/base/collection';

const QuickAddCollection = BaseCollection.extend({
    /**
     * @type {Object<string, Array<QuickAddModel>>}
     */
    _index: null,

    productBySkuRoute: 'oro_frontend_autocomplete_search',

    loadProductsBatchSize: 500,

    model: QuickAddModel,

    ajaxOptions: {},

    constructor: function QuickAddCollection(data, options) {
        Object.assign(this, _.pick(options, 'productBySkuRoute', 'loadProductsBatchSize', 'ajaxOptions'));

        this._index = {_: []};

        this.listenTo(this, {
            add: this.onModelAdd,
            remove: this.onModelRemove,
            change: this.onModelChange,
            reset: this.onReset,
            update: this.onUpdate
        });
        QuickAddCollection.__super__.constructor.call(this, data, options);
    },

    onModelAdd(model) {
        const key = this._formatIndexKey(model.get('sku'), model.get('unit_label'));
        this._addToIndex(key, model);
    },

    onModelRemove(model) {
        const key = this._formatIndexKey(model.get('sku'), model.get('unit_label'));
        this._removeFromIndex(key, model);
    },

    onModelChange(model) {
        const previousKey = this._formatIndexKey(model.previous('sku'), model.previous('unit_label'));
        const key = this._formatIndexKey(model.get('sku'), model.get('unit_label'));
        if (key !== previousKey) {
            this._removeFromIndex(previousKey, model);
            this._addToIndex(key, model);
        }
    },

    onReset(collection, options) {
        options.previousModels.forEach(model => this.onModelRemove(model));
    },

    onUpdate(collection, options) {
        const {removed: removedModels = []} = options.changes || {};
        removedModels.forEach(model => model.trigger('removed'));
    },

    _formatIndexKey(sku, unitLabel) {
        return `${(sku || '').toUpperCase()}_${(unitLabel || '').toUpperCase()}`;
    },

    _addToIndex(key, model) {
        if (!this._index[key]) {
            this._index[key] = [];
        }
        this._index[key].push(model);
    },

    _removeFromIndex(key, model) {
        if (this._index[key]) {
            const i = this._index[key].indexOf(model);
            if (i !== -1) {
                this._index[key].splice(i, 1);
            }
            if (!this._index[key].length) {
                delete this._index[key];
            }
        }
    },

    /**
     * Check if model with proper sku and unit_label exists
     *
     * @param {[string,string]|Object<{sku:string,unit:string}>} args
     * @return {QuickAddModel|null}
     */
    findCompatibleModel(...args) {
        let sku;
        let unitLabel;
        if (args.length === 1) {
            ({sku, unit_label: unitLabel} = args[0]);
        } else {
            ([sku, unitLabel] = args);
        }
        const key = this._formatIndexKey(sku, unitLabel);
        const models = this._index[key];
        return models && models.length ? models[0] : null;
    },

    /**
     * Return existing empty model or create new empty model
     *
     * @return {QuickAddModel}
     */
    getEmptyModel() {
        const models = this._index['_'] || [];
        let model = models[0];
        if (!model) {
            // create new empty model
            model = this.push({});
        }
        return model;
    },

    /**
     * Updates the collection with supplied items and loads product information
     *
     * @param {Array<{sku:string, quantity: string, unit_label?: string}>} items
     * @param {Object} options
     * @param {boolean=} options.ignoreIncorrectUnit by default product with incorrect units are added to collection
     * @return {Promise<{invalid: Object}>}
     */
    addQuickAddRows(items, options = {}) {
        const promise = this._addQuickAddRows(items, options);
        this.trigger('quick-add-rows', promise);
        return promise;
    },

    /**
     * Updates the collection with supplied items and loads product information
     *
     * @param {Array<{sku:string, quantity: string, unit_label?: string}>} items
     * @param {Object} options
     * @param {boolean=} options.ignoreIncorrectUnit by default product with incorrect units are added to collection
     * @return {Promise<{invalid: Object}>}
     * @protected
     */
    async _addQuickAddRows(items, options = {}) {
        const itemsToLoad = {};

        await window.sleep(0); // give time to repaint UI

        if (this._index['_']) {
            this._index['_'] // sort empty models by rows order in form
                .sort((ma, mb) => ma.get('_order') - mb.get('_order'));
        }

        items.forEach(item => {
            let model = this.findCompatibleModel(item);
            if (model) {
                // update existing model
                model.set('quantity', model.get('quantity') + item.quantity);
            } else {
                model = this.getEmptyModel();
                model.set({
                    sku: item.sku.toUpperCase(),
                    quantity: item.quantity,
                    unit_label: item.unit_label
                });
            }

            if (!model.get('product_name')) {
                // the product info not loaded yet, then add to load list
                itemsToLoad[model.cid] = item;
            }
        });

        this.trigger('quick-add-rows:before-load');

        let result;
        try {
            result = await this.loadProductInfo(itemsToLoad, options);
        } catch (e) {
            throw e;
        } finally {
            let invalidItems;
            if (result) {
                ({invalid: invalidItems = {}} = result);
            } else {
                invalidItems = itemsToLoad;
            }

            const invalidModels = Object.keys(invalidItems).map(cid => this.get(cid));

            this.remove(invalidModels);
        }
        this.trigger('quick-add-rows:after-load');

        return result;
    },

    /**
     *
     * @param {Array<{sku:string, quantity: string, unit_label?: string}>} items
     * @param {Object} options
     * @param {boolean=} options.ignoreIncorrectUnit by default product with incorrect units are added to collection
     * @return {Promise<{invalid: any}>}
     */
    async loadProductInfo(items, options = {}) {
        let remainingItems = Object.assign(items);

        const batches = _.chunk(_.unique(_.pluck(Object.values(items), 'sku')), this.loadProductsBatchSize);
        await batches.reduce(async (previousRequest, batch) => {
            await previousRequest;
            return new Promise((resolve, reject) => {
                if (!batch.length) {
                    resolve({});
                } else {
                    const routeParams = {
                        name: 'oro_product_visibility_limited_with_prices',
                        per_page: batch.length,
                        query: ''
                    };
                    $.ajax(Object.assign({
                        url: routing.generate(this.productBySkuRoute, routeParams),
                        method: 'post',
                        data: {
                            sku: batch
                        }
                    }, this.ajaxOptions)).done((data, status) => {
                        if (status === 'success') {
                            remainingItems = this._updateModels(data.results, remainingItems, options);
                            resolve();
                        } else {
                            reject(new Error('Invalid response'));
                        }
                    }).fail(e => reject(e));
                }
            });
        }, {});

        return Promise.resolve({invalid: remainingItems});
    },

    /**
     * Processes loaded product info data, update related models and returns items that were not updated
     *
     * @param {Array<Object>} productInfo list of loaded product info data
     * @param {Object} itemsToUpdate map of model's CIDs to base product info data
     * @param {Object} options
     * @param {boolean=} options.ignoreIncorrectUnit by default product with incorrect units are added to collection
     * @return {Object} remaining items that was not updated
     * @private
     */
    _updateModels(productInfo, itemsToUpdate, options= {}) {
        const updatedModels = [];
        productInfo.forEach(data => {
            const {id, sku, units, 'defaultName.string': productName, ...extraAttrs} = data;
            const SKU = sku.toUpperCase();
            this.where(model => model.get('sku').toUpperCase() === SKU && model.get('product_name') === '')
                .forEach(model => {
                    const attrs = {
                        sku,
                        product_name: productName,
                        units_loaded: typeof units !== 'undefined',
                        product_units: {...units},
                        ...extraAttrs
                    };
                    model.set(attrs);

                    const unitLabel = itemsToUpdate[model.cid].unit_label;
                    if (model.get('unit') || !unitLabel || options.ignoreIncorrectUnit !== false) {
                        // unit is resolved properly, or no wishful unit, or incorrect unit has to be ignored
                        // and to list to models that going to be updated
                        updatedModels.push(model.cid);
                    }
                });
        });

        return _.omit(itemsToUpdate, updatedModels);
    }
});

export default QuickAddCollection;

