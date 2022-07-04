import __ from 'orotranslation/js/translator';
import BaseModel from 'oroui/js/app/models/base/model';

const MIME_TYPES = {
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.gif': 'image/gif',
    '.svg': 'image/svg+xml',
    '.png': 'image/png',
    '.webp': 'image/webp'
};

const getMimeType = url => {
    const ext = url.match(/\.\w{3,4}($|\?)/g);
    if (!ext) {
        return __('oro.cms.wysiwyg.dialog.picture_settings.unknown_type');
    }
    return MIME_TYPES[ext[0]] || '';
};

const PictureSettingsModel = BaseModel.extend({
    defaults: {
        attributes: {},
        preview: null,
        invalid: false,
        errorMessage: '',
        main: false,
        index: 0,
        sortable: true
    },

    constructor: function PictureSettingsModel(...args) {
        PictureSettingsModel.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.createPreviewFilter(options);
        PictureSettingsModel.__super__.initialize.call(this, options);
    },

    createPreviewFilter(source) {
        const {src, srcset} = source.attributes;
        if (!src && !srcset) {
            return;
        }
        this.set('preview', this.replaceSrcset(src ? src : srcset));
        this.set('attributes', {
            ...source.attributes,
            type: getMimeType(src ? src : srcset)
        });
    },

    replaceSrcset(url) {
        return url.replace('wysiwyg_original', 'digital_asset_in_dialog');
    },

    updateImageUrl(url) {
        const attributes = this.get('attributes');
        if (attributes.srcset) {
            attributes.srcset = url;
        }
        if (attributes.src) {
            attributes.src = url;
        }

        this.set('attributes', attributes);
        this.createPreviewFilter({
            attributes
        });
        this.trigger('change:attributes');
    },

    updateAttribute(name, value, options = {}) {
        const attributes = this.get('attributes');
        this.set('attributes', {
            ...attributes,
            [`${name}`]: value
        }, options);
    },

    getAttribute(name) {
        return this.get('attributes')[name];
    }
});

export default PictureSettingsModel;
