import BaseCollection from 'oroui/js/app/models/base/collection';
import PictureSettingsModel from './picture-settings-model';

const PictureSettingsCollection = BaseCollection.extend({
    model: PictureSettingsModel,

    comparator: 'index',

    constructor: function PictureSettingsCollection(...args) {
        PictureSettingsCollection.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        options.forEach((item, index) => item.index = !item.main ? index : '9999');
        PictureSettingsCollection.__super__.initialize.apply(this, options);
    },

    getData() {
        return {
            sources: this.toJSON().filter(({main}) => !main),
            main: this.toJSON().filter(({main}) => main)[0]
        };
    },

    updateSortable() {
        this.each(model => model.set('sortable', this.models.length > 2));
    }
});

export default PictureSettingsCollection;
