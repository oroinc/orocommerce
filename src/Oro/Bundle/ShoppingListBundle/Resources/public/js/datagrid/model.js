import Backbone from 'backbone';

const ShoppingListItemModel = Backbone.Model.extend({
    constructor: function ShoppingListItemModel(attributes, options) {
        return ShoppingListItemModel.__super__.constructor.call(this, attributes, options);
    },

    initialize(attributes, options) {
        ShoppingListItemModel.__super__.initialize.call(this, attributes, options);
        this.isGroup = String(this.get('id')).indexOf('_') !== -1;
    },

    subModels() {
        return this.get('ids').map(id => this.collection.get(id));
    },

    classList() {
        const model = this;
        const rowClassName = model.get('row_class_name') || '';
        const classList = rowClassName.split(' ');

        return {
            add(className) {
                classList.push(className);
                model.set('row_class_name', classList.join(' '));
            },
            remove(className) {
                const index = classList.indexOf(className);
                if (index !== -1) {
                    classList.splice(index, 1);
                }

                model.set('row_class_name', classList.join(' '));
            }
        };
    }
});

export default ShoppingListItemModel;
