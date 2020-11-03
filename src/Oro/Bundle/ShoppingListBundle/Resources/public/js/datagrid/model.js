import Backbone from 'backbone';
import _ from 'underscore';
import quantityHelper from 'oroproduct/js/app/quantity-helper';

const ShoppingListItemModel = Backbone.Model.extend({
    constructor: function ShoppingListItemModel(attributes, options) {
        return ShoppingListItemModel.__super__.constructor.call(this, attributes, options);
    },

    initialize(attributes, options) {
        ShoppingListItemModel.__super__.initialize.call(this, attributes, options);
        this.isGroup = String(this.get('id')).indexOf('_') !== -1;

        if (!this.get('isConfigurable')) {
            this.set('precision', this.getQuantityPrecision(), {silent: true});
        }
    },

    getQuantityPrecision() {
        const units = this.get('units');
        let precision = quantityHelper.getDefaultMaxFractionDigits();

        if (units) {
            const unitData = units[this.get('unit')];

            if (_.isObject(unitData)) {
                precision = unitData.precision;
            }
        }

        return precision;
    },

    subModels() {
        return (this.get('ids') || []).map(id => this.collection.get(id));
    },

    classList() {
        const model = this;
        const rowClassName = model.get('row_class_name') || '';
        const classList = rowClassName.split(' ');

        return {
            add(className) {
                if (!className) {
                    return new Error(`'className' should not be empty`);
                }
                classList.push(className);
                model.set('row_class_name', _.uniq(classList).join(' '));
            },
            remove(className) {
                if (!className) {
                    return new Error(`'className' should not be empty`);
                }
                const index = classList.indexOf(className);
                if (index !== -1) {
                    classList.splice(index, 1);
                }

                model.set('row_class_name', _.uniq(classList).join(' '));
            }
        };
    }
});

export default ShoppingListItemModel;
