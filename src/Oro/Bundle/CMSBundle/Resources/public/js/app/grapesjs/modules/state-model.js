import BaseModel from 'oroui/js/app/models/base/model';

const StateModel = BaseModel.extend({
    defaults: {
        codeMode: false,
        isolateScopeId: null
    },

    constructor: function StateModel(...args) {
        return StateModel.__super__.constructor.apply(this, args);
    },

    getIsolateScopeId() {
        return this.get('isolateScopeId') ? this.get('isolateScopeId') : undefined;
    },

    getCodeMode() {
        return this.get('codeMode');
    }
});

export default StateModel;
