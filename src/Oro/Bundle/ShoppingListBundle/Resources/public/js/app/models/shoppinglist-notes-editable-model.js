import routing from 'routing';
import BaseModel from 'oroui/js/app/models/base/model';
import mediator from 'oroui/js/mediator';

const ShoppingListNotesEditableModel = BaseModel.extend({
    route: 'oro_api_frontend_patch_entity_data',

    urlRoot: null,

    defaults: {
        notes: ''
    },

    constructor: function ShoppingListNotesEditableModel(...args) {
        ShoppingListNotesEditableModel.__super__.constructor.apply(this, args);
    },

    initialize: function(options) {
        this.urlRoot = routing.generate(this.route, options.routingOptions);
        this.listenTo(mediator, `shopping-list-${this.id}-notes:update`, this.onShoppingListNotes);
        this.listenTo(this, 'sync',
            (...args) => mediator.trigger(`shopping-list-${this.id}-notes:sync`, ...args)
        );

        ShoppingListNotesEditableModel.__super__.initialize.call(this, options);
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
