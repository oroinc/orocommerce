import BaseModel from 'oroui/js/app/models/base/model';

const SettingsModel = BaseModel.extend({
    defaults: {},

    constructor: function SettingsModel(...args) {
        return SettingsModel.__super__.constructor.apply(this, args);
    }
});

export default SettingsModel;
