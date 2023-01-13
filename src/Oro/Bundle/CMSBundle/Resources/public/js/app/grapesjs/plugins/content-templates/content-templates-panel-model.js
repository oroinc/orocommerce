import BaseModel from 'oroui/js/app/models/base/model';

const ContentTemplatesPanelModel = BaseModel.extend({
    defaults: {
        blockData: [],
        allCollapsed: false
    },

    constructor: function ContentTemplatesPanelModel(...args) {
        ContentTemplatesPanelModel.__super__.constructor.apply(this, args);
    },

    getBlockData() {
        return this.get('blockData');
    }
});

export default ContentTemplatesPanelModel;
