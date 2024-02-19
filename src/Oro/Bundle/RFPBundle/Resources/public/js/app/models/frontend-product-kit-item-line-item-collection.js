import _ from 'underscore';
import BaseCollection from 'oroui/js/app/models/base/collection';

const FrontendProductKitItemLineItemCollection = BaseCollection.extend({
    constructor: function FrontendProductKitItemLineItemCollection(options) {
        FrontendProductKitItemLineItemCollection.__super__.constructor.call(this, options);
    },

    add: function(...args) {
        FrontendProductKitItemLineItemCollection.__super__.add.call(this, ...args);

        this.sort();
    },

    sort: function() {
        this.models = _.sortBy(this.models, model => {
            return model.get('sortOrder');
        });
    }
});

export default FrontendProductKitItemLineItemCollection;
