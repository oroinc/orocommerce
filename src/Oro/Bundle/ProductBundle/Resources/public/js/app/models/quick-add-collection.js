import QuickAddModel from 'oroproduct/js/app/models/quick-add-model';
import BaseCollection from 'oroui/js/app/models/base/collection';

const QuickAddCollection = BaseCollection.extend({
    /**
     * @type {Object<string, Array<QuickAddModel>>}
     */
    _index: null,

    comparator: 'index',

    model: QuickAddModel,

    constructor: function QuickAddCollection(data, options) {
        this._index = {__: []};

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
        const key = this._formatIndexKey(model.get('sku'), model.get('unit_label'), model.get('organization'));
        this._addToIndex(key, model);
    },

    onModelRemove(model) {
        const key = this._formatIndexKey(model.get('sku'), model.get('unit_label'), model.get('organization'));
        this._removeFromIndex(key, model);
    },

    onModelChange(model) {
        const previousKey = this._formatIndexKey(
            model.previous('sku'),
            model.previous('unit_label'),
            model.previous('organization')
        );
        const key = this._formatIndexKey(model.get('sku'), model.get('unit_label'), model.get('organization'));
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

    _formatIndexKey(sku, unitLabel, organization) {
        return `${(sku || '').toUpperCase()}_${(unitLabel || '').toUpperCase()}_${(organization || '').toUpperCase()}`;
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
     * @param {Object<{sku:string, unit_label:string}>} attrs
     * @return {QuickAddModel|null}
     */
    findCompatibleModel({sku, unit_label: unitLabel, organization}) {
        const key = this._formatIndexKey(sku, unitLabel, organization);
        const models = this._index[key];
        return models && models.length ? models[0] : null;
    },

    /**
     * Return existing empty model or create new empty model
     *
     * @return {QuickAddModel}
     */
    getEmptyModel() {
        const models = this._index['__'] || [];
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
     * @param {Array<{sku:string, quantity: string, unit_label?: string, index?: number}>} items
     * @param {Object} options
     * @param {boolean=} options.ignoreIncorrectUnit by default product with incorrect units are added to collection
     * @param {string=} options.strategy Either "update" or "replace"
     */
    addQuickAddRows(items, options = {}) {
        if (this._index['__']) {
            this._index['__'] // sort empty models by rows order in form
                .sort((ma, mb) => ma.get('index') - mb.get('index'));
        }

        items.forEach(attrs => {
            const {
                product_name: productName = '',
                units = {},
                unit_label: unitLabel,
                quantity
            } = attrs;
            const unitsLoaded = attrs.units !== undefined;
            const sku = attrs.sku.toUpperCase();
            const organization = attrs.organization || '';
            let model;

            if (attrs.index) {
                // get existing model by index
                model = this.find(model => model.get('index') === attrs.index);
            } else {
                // get model with the same pair of sku+unit
                model = this.findCompatibleModel(attrs);
            }

            if (model) {
                let quantity = attrs.quantity;
                if (options.strategy !== 'replace') {
                    quantity += model.get('quantity');
                }
                // update existing model
                model.set({
                    product_name: productName,
                    organization: organization,
                    product_units: units,
                    units_loaded: unitsLoaded,
                    quantity
                });
            } else {
                model = this.getEmptyModel();
                model.set({
                    sku,
                    product_name: productName,
                    organization: organization,
                    product_units: units,
                    units_loaded: unitsLoaded,
                    unit_label: unitLabel,
                    quantity
                });
            }

            // Reset errors to make sure that 'error' event will be triggered even if errors are not changed
            if (model.get('errors') !== void 0) {
                model.set('errors', [], {silent: true});
            }

            const {additional = {}, errors = []} = attrs;
            // update rest of attributes
            model.set({errors, ...additional});
        });

        this.trigger('quick-add-rows');
    }
});

export default QuickAddCollection;

