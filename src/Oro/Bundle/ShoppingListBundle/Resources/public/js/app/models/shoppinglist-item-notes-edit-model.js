import routing from 'routing';
import BaseModel from 'oroui/js/app/models/base/model';

const ShoppinglistItemNotesEditModel = BaseModel.extend({
    defaults: {
        notes: ''
    },

    url() {
        return routing.generate('oro_shopping_list_frontend_line_item_patch_notes', {id: this.id});
    },

    constructor: function ShoppinglistItemNotesEditModel(...args) {
        ShoppinglistItemNotesEditModel.__super__.constructor.apply(this, args);
    },

    isEmptyNotes() {
        return this.get('notes').length === 0;
    }
});

export default ShoppinglistItemNotesEditModel;
