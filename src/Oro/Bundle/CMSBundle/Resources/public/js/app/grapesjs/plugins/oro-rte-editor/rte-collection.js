import BaseCollection from 'oroui/js/app/models/base/collection';
import RteModel from './rte-model';

const RteCollection = BaseCollection.extend({
    model: RteModel,

    comparator: 'order',

    constructor: function RteCollection(...args) {
        RteCollection.__super__.constructor.apply(this, args);
    }
});

export default RteCollection;
