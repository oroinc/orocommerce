import BaseModel from 'oroui/js/app/models/base/model';

const RteModel = BaseModel.extend({
    defaults: {
        event: 'click',
        classes: {
            button: 'gjs-rte-action',
            active: 'gjs-rte-active',
            disabled: 'gjs-rte-disabled',
            inactive: 'gjs-rte-inactive'
        }
    },

    constructor: function RteModel(options) {
        RteModel.__super__.constructor.call(this, options);
    },

    getClass(className) {
        return this.get('classes')[className] || '';
    }
});

export default RteModel;
