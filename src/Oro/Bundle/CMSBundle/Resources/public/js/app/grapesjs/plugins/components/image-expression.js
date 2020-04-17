import DigitalAssetHelper from 'orocms/js/app/grapesjs/helpers/digital-asset-helper';

export default class ImageExpression {
    /**
     * @constructor
     * @param editor
     * @param options
     */
    constructor(editor, ...options) {
        this.editor = editor;
        this.onLoad = this.onLoad.bind(this);

        this.normalizeBgURLString = this.normalizeBgURLString.bind(this);
        this.bindEvents();
    }

    destroy() {
        this.unbindEvents();

        delete this.onLoad;
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        this.editor.on('component:styleUpdate:background-image', this.normalizeBgURLString);
        this.editor.on('load', this.onLoad);
    }

    /**
     * Bind event listeners
     */
    unbindEvents() {
        this.editor.off('component:styleUpdate:background-image', this.normalizeBgURLString);
        this.editor.off('load', this.onLoad);
    }

    /**
     * @param model
     */
    normalizeBgURLString(model) {
        const styles = model.getStyle();
        const imgId = DigitalAssetHelper.getDigitalAssetIdFromTwigTag(styles['background-image']);

        // Replace 'wysiwyg_image' placeholder by real url
        if (imgId) {
            const url = imgId.map(id => {
                return `url("${DigitalAssetHelper.getImageUrl(id)}")`;
            }).join(', ');

            model.setStyle({'background-image': url});
        }
    }

    /**
     * on load listener
     */
    onLoad() {
        this.normalizeBgURLString(this.editor.getWrapper());
    }
}
