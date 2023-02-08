import __ from 'orotranslation/js/translator';
import BaseClass from 'oroui/js/base-class';
import Modal from 'oroui/js/modal';
import HTMLValidator from 'orocms/js/app/grapesjs/validation';
import {escapeWrapper, escapeCss} from 'orocms/js/app/grapesjs/plugins/components/content-isolation';

const CodeValidator = BaseClass.extend({
    validation: {
        rules: {
            content: {
                'Oro\\Bundle\\CMSBundle\\Validator\\Constraints\\WYSIWYG': {
                    message: 'oro.cms.wysiwyg.not_permitted_content.message'
                }
            },
            styles: {
                'Oro\\Bundle\\CMSBundle\\Validator\\Constraints\\WYSIWYGStyle': {
                    message: 'oro.cms.wysiwyg.not_permitted_style.message'
                }
            }
        }
    },

    constructor: function CodeValidator(options) {
        CodeValidator.__super__.constructor.call(this, options);
    },

    initialize({editor}) {
        this.editor = editor;
        this.validator = new HTMLValidator({
            editor
        });
        this.invalid = false;
        this.restrictFaild = false;

        this.editor.once('load', this.onLoadEditor.bind(this));
    },

    /**
     * Handle editor is initialize
     */
    onLoadEditor() {
        this.parentView = this.editor.parentView;
        this.$stylesInputElement = this.parentView.$stylesInputElement;
        this.$textInputElement = this.parentView.$el;

        this.$stylesInputElement.data('wysiwyg:validation', {
            contentValidate: this.contentValidate.bind(this)
        });
        this.$textInputElement.data('wysiwyg:validation', {
            restrictionValidate: this.restrictionValidate.bind(this)
        });

        this.ComponentRestriction = this.editor.ComponentRestriction;

        this.listenTo(this.parentView, 'dispose', this.onDispose);
        this.listenTo(this.editor.importDialogView, 'import:after', this.unLockEditor);

        this.initValidation();
    },

    initValidation() {
        const contentDataValidation = this.$textInputElement.data('validation');
        const styleDataValidation = this.$stylesInputElement.data('validation');

        this.$textInputElement.data('validation',
            Object.assign({...this.validation.rules.content, ...contentDataValidation})
        );
        this.$stylesInputElement.data('validation',
            Object.assign({...this.validation.rules.styles, ...styleDataValidation})
        );

        this.$textInputElement.valid();
        this.$stylesInputElement.valid();
    },

    /**
     * Get raw editor content
     * @returns {string}
     */
    getRawContent() {
        return `${escapeWrapper(this.$textInputElement.val())}
            <style>${escapeCss(this.$stylesInputElement.val())}</style>`;
    },

    /**
     * Open content editor as Import dialog
     */
    openContentEditor() {
        const {Commands} = this.editor;

        if (Commands.has('gjs-open-import-webpage')) {
            Commands.run('gjs-open-import-webpage', {
                content: this.getRawContent(),
                exportButton: false
            });
        }
    },

    /**
     * Lock editor till user fix problem
     */
    lockEditor() {
        if (!this.isInvalid()) {
            return;
        }

        this.createOverlay();
    },

    /**
     * Unlock editor if source code became valid
     */
    unLockEditor() {
        if (this.isInvalid()) {
            return;
        }

        this.removeOverlay();
    },

    /**
     * Create lock overlay
     */
    createOverlay() {
        if (this.lockOverlay) {
            return;
        }

        this.lockOverlay = new Modal({
            autoRender: true,
            title: __('oro.htmlpurifier.lock_editor.title'),
            content: __('oro.htmlpurifier.lock_editor.desc'),
            okText: __('oro.htmlpurifier.lock_editor.open_editor'),
            className: 'modal oro-modal-danger editor-lock-overlay',
            allowClose: false,
            allowCancel: false,
            okButtonClass: 'btn btn-danger open-code-editor',
            attributes: {
                role: 'alertdialog'
            }
        });

        this.lockOverlay.$el.appendTo(this.editor.getEl());
        this.lockOverlay.$el.on(
            `click${this.lockOverlay.eventNamespace()}`,
            '.open-code-editor',
            this.openContentEditor.bind(this)
        );
    },

    /**
     * Remove lock overlay
     */
    removeOverlay() {
        if (!this.lockOverlay) {
            return;
        }

        this.lockOverlay.$el.off(this.lockOverlay.eventNamespace());
        this.lockOverlay.dispose();
        delete this.lockOverlay;
    },

    /**
     * Validate content
     * @param {string} str
     * @param {boolean} allowLock
     * @param {object} options
     * @returns {(string|Array)}
     */
    validate(str, {allowLock = true, ...options} = {}) {
        const res = this.validator.validate(str, options);

        this.invalid = !!res.length;

        if (this.invalid && allowLock) {
            this.lockEditor();
        }

        return res;
    },

    /**
     * Is editor source code is invalid
     * @returns {boolean}
     */
    isInvalid() {
        return this.invalid || this.restrictFaild;
    },

    /**
     * Content browser parser validator
     * @returns {(string|Array)}
     */
    contentValidate() {
        return this.validate(this.getRawContent()).map(({shortMessage}) => shortMessage);
    },

    /**
     * Get restricted tags validation
     * @returns {(string|Array)}
     */
    restrictionValidate(content) {
        if (!this.parentView.allow_tags && !content) {
            return [];
        }

        const res = this.ComponentRestriction.validate(escapeWrapper(content ?? this.$textInputElement.val()));
        this.restrictFaild = !!res.length;
        if (this.restrictFaild) {
            this.lockEditor();
        }

        return res;
    },

    onDispose() {
        this.$stylesInputElement.removeData('wysiwyg:validation');
        this.$textInputElement.removeData('wysiwyg:validation');
        this.removeOverlay();

        delete this.parentView;
        delete this.$stylesInputElement;
        delete this.$textInputElement;
        delete this.ComponentRestriction;
        delete this.invalid;

        this.dispose();
    }
});

export default (editor, options) => {
    editor.CodeValidator = new CodeValidator({editor, ...options});
};
