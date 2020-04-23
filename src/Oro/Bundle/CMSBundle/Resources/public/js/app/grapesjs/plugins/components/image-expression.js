export default class ImageExpression {
    /**
     * @constructor
     * @param editor
     * @param options
     */
    constructor(editor, ...options) {
        this.editor = editor;

        this.normalizeBgURLString = this.normalizeBgURLString.bind(this);
        this.bindEvents();
    }

    destroy() {
        this.unbindEvents();
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        this.editor.on('component:styleUpdate:background-image', this.normalizeBgURLString);
        this.editor.on('load', this.normalizeBgURLString);
    }

    /**
     * Bind event listeners
     */
    unbindEvents() {
        this.editor.off('component:styleUpdate:background-image', this.normalizeBgURLString);
        this.editor.off('load', this.normalizeBgURLString);
    }

    normalizeBgURLString() {
        // Need re-render all styles to show images with the correct url in the canvas for preview but do not save that.
        this.editor.CssComposer.render();
    }
}
