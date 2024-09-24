import routing from 'routing';
import BaseModel from 'oroui/js/app/models/base/model';

const ShoppinglistItemNotesEditModel = BaseModel.extend({
    defaults: {
        notes: ''
    },

    url() {
        return routing.generate('oro_api_frontend_patch_entity_data', {
            id: this.id,
            className: 'Oro_Bundle_ShoppingListBundle_Entity_LineItem'
        });
    },

    constructor: function ShoppinglistItemNotesEditModel(...args) {
        ShoppinglistItemNotesEditModel.__super__.constructor.apply(this, args);
    },

    isEmptyNotes() {
        return this.get('notes').length === 0;
    }
});

export default ShoppinglistItemNotesEditModel;
