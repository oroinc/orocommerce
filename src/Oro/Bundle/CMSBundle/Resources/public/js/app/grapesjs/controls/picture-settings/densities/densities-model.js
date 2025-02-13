import BaseModel from 'oroui/js/app/models/base/model';
import {getMimeType} from '../picture-settings-model';

const DensitiesModel = BaseModel.extend({
    defaults: {
        density: 1,
        url: '',
        preview: '',
        mimeType: '',
        origin: false
    },

    constructor: function DensitiesModel(...args) {
        DensitiesModel.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.set('getAvailableOptions', this.getAvailableOptions.bind(this));
        this.set('getMemeType', this.getMemeType.bind(this));

        if (options.url) {
            this.updateImageUrl(options.url);
        }

        DensitiesModel.__super__.initialize.call(this, options);
    },

    getAvailableOptions() {
        return this.collection.getAvailableOptions();
    },

    getMemeType(url) {
        return getMimeType(url);
    },

    replaceSrcset(url) {
        return url.replace('wysiwyg_original', 'digital_asset_in_dialog');
    },

    updateImageUrl(url) {
        this.set('url', url);
        this.set('preview', this.replaceSrcset(url));
    },

    toSrcSet() {
        return this.get('url') ? `${this.get('url')} ${this.get('density')}x` : '';
    }
});

export default DensitiesModel;
