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
    }
});

export default ShoppingListItemModel;
