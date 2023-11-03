import BaseModel from 'oroui/js/app/models/base/model';

const IconModel = BaseModel.extend({
    defaults: {
        id: '',
        title: '',
        themeName: 'default',
        showLabel: true,
        selected: false
    },

    constructor: function IconModel(...args) {
        IconModel.__super__.constructor.apply(this, args);
    }
});

export default IconModel;
