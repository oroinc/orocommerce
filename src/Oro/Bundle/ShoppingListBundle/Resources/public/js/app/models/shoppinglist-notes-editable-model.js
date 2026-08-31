import routing from 'routing';
import BaseModel from 'oroui/js/app/models/base/model';
import mediator from 'oroui/js/mediator';

const ShoppingListNotesEditableModel = BaseModel.extend({
    route: 'oro_shopping_list_frontend_patch_notes',

    defaults: {
        notes: ''
    },

    constructor: function ShoppingListNotesEditableModel(...args) {
        ShoppingListNotesEditableModel.__super__.constructor.apply(this, args);
    },

    initialize: function(options) {
        this.listenTo(mediator, `shopping-list-${this.id}-notes:update`, this.onShoppingListNotes);
        this.listenTo(this, 'sync',
            (...args) => mediator.trigger(`shopping-list-notes:sync`, ...args)
        );

        ShoppingListNotesEditableModel.__super__.initialize.call(this, options);
    },

    url() {
        return routing.generate(this.route, {id: this.id});
    },

    isEmptyNotes() {
        return this.get('notes').length === 0;
    },

    onShoppingListNotes(notes) {
        this.save({notes}, {
            patch: true,
            wait: false
        });
    }
});

export default ShoppingListNotesEditableModel;
