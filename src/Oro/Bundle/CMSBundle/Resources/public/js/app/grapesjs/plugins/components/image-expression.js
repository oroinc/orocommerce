import DigitalAssetHelper from 'orocms/js/app/grapesjs/helpers/digital-asset-helper';

export default class ImageExpression {
    /**
     * @constructor
     * @param editor
     * @param options
     */
    constructor(editor, ...options) {
        this.editor = editor;

        this.bindEvents();
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        this.editor.on('component:styleUpdate:background-image', this.applyInlineBackground.bind(this));
        this.editor.on('load', this.onLoad.bind(this));
    }

    /**
     * Apply background image after change property
     * @param model
     */
    applyInlineBackground(model) {
        if (!model) {
            return;
        }

        const selected = this.editor.getSelected();
        const style = model.get('style');
        this.emulateInlineBackground(style, selected.view.$el);
    }

    /**
     * Add inline style with background image
     * @param style
     * @param elem
     */
    emulateInlineBackground(style, elem) {
        if (style['background-image'] && style['background-image'] !== 'none') {
            const imageId = DigitalAssetHelper.getDigitalAssetIdFromTwigTag(style['background-image']);

            if (imageId) {
                elem.css('background-image', imageId.map(id => {
                    return `url(${DigitalAssetHelper.getImageUrl(id)})`;
                }).join(', '));
            }
        } else {
            elem.css('background-image', '');
        }
    }

    /**
     * Emulate background after editor loaded
     * @param model
     */
    triggerStyleChange(model) {
        const rule = model.rule;

        if (rule) {
            const style = rule.get('style');

            this.emulateInlineBackground(style, model.view.$el);
        }

        const components = model.get('components');

        if (components.length) {
            components.forEach(this.triggerStyleChange.bind(this));
        }
    }

    /**
     * on load listener
     */
    onLoad() {
        const {models} = this.editor.DomComponents.getComponents();

        models.forEach(this.triggerStyleChange.bind(this));
    }
}
