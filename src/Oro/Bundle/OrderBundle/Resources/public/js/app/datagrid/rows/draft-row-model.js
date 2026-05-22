import Backbone from 'backbone';

const DraftRowModel = Backbone.Model.extend({
    idAttribute: 'orderLineItemId',

    defaults: {
        editMode: false,
        isUpdated: false
    },

    constructor: function DraftRowModel(attributes, options) {
        return DraftRowModel.__super__.constructor.call(this, attributes, options);
    },

    initialize(attributes, options) {
        DraftRowModel.__super__.initialize.call(this, attributes, options);

        if (attributes && attributes.isValid === '0') {
            this.set('editMode', true);
        }
    }
});

export default DraftRowModel;

