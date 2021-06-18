import ViewComponent from 'oroui/js/app/components/view-component';
import NotesModel from 'oroshoppinglist/js/app/models/shoppinglist-notes-editable-model';

const ShoppingListNotesEditableViewComponent = ViewComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function ShoppingListNotesEditableViewComponent(options) {
        ShoppingListNotesEditableViewComponent.__super__.constructor.call(this, options);
    },

    /**
     * @param {Object} options
     */
    initialize(options) {
        options.model = new NotesModel({
            id: options.shoppingListId,
            routingOptions: {...options.routingOptions},
            notes: options.notes
        });

        ShoppingListNotesEditableViewComponent.__super__.initialize.call(this, options);
    }
});

export default ShoppingListNotesEditableViewComponent;
