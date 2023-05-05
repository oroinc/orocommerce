import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orocms/templates/grapesjs-import-dialog-template.html';
import DialogWidget from 'oro/dialog-widget';
import {
    stripRestrictedAttrs,
    escapeWrapper,
    separateContent
} from 'orocms/js/app/grapesjs/plugins/components/content-isolation';
import {unescapeTwigExpression} from '../../utils';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import ApiAccessor from 'oroui/js/tools/api-accessor';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import StandartConfirmation from 'oroui/js/standart-confirmation';

const REGEXP_TWIG_TAGS = /\{\{([\w\s\"\'\_\-\,\&\#\;\(\)]+)\}\}/gi;

function messageCheck(str) {
    const REGEXP_LINK_DOC = /<a\b[^>]* href=\"[\w\d\/\:\#\.]+\" target=\"_blank+\">(.*?)<\/a>/gi;
    const REGEXP_LINK_ALL = /<a((?!<)(.))*?\>(.*?)<((?!<)(.|\n))*?\>/gi;

    const linkMatch = str.match(REGEXP_LINK_DOC) || [];
    const linkAllMatch = str.match(REGEXP_LINK_ALL) || [];

    return {
        done: linkMatch.length > 0 && linkMatch.length === linkAllMatch.length,
        string: str.split('\n').map(subStr => {
            REGEXP_LINK_DOC.lastIndex = 0;
            if (REGEXP_LINK_DOC.test(subStr)) {
                return subStr.replace(REGEXP_LINK_DOC, (match, content) => {
                    return match.replace(content, _.escape(content));
                });
            }

            return _.escape(subStr);
        }).join('\n')
    };
}

const ImportDialogView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'editor', 'importViewerOptions',
        'modalImportLabel', 'modalImportTitle', 'modalImportButton',
        'validateApiProps', 'entityClass', 'fieldName', 'commandId',
        'importCallback', 'modalExportButton'
    ]),

    /**
     * @inheritdoc
     */
    autoRender: false,

    /**
     * @property {GrapesJS.Instance}
     */
    editor: null,

    /**
     * @property {CodeMirror.Model}
     */
    codeViewer: null,

    /**
     * @property {CodeMirror.Editor}
     */
    viewerEditor: null,

    /**
     * @property {Object}
     */
    importViewerOptions: {
        codeName: 'htmlmixed',
        theme: 'hopscotch',
        readOnly: 0
    },

    /**
     * @property {String}
     */
    modalImportLabel: __('oro.cms.wysiwyg.import.label'),

    /**
     * @property {String}
     */
    modalImportTitle: __('oro.cms.wysiwyg.import.title'),

    /**
     * @property {String}
     */
    modalImportButton: __('oro.cms.wysiwyg.import.button'),

    /**
     * @property {String}
     */
    modalExportButton: __('oro.cms.wysiwyg.import.export_button'),

    /**
     * @property {Object}
     */
    dialog: null,

    /**
     * @property {Object}
     */
    dialogOptions: {},

    /**
     * @property {String}
     */
    commandId: null,

    /**
     * @property {String}
     */
    content: '',

    /**
     * @property {Boolean}
     */
    disabled: false,

    /**
     * @property {Function}
     */
    template: template,

    validateApiProps: {
        http_method: 'POST',
        route: 'oro_cms_wysiwyg_content_validate'
    },

    twigApiResolverProps: {
        http_method: 'POST',
        route: 'oro_cms_wysiwyg_content_resolve'
    },

    entityClass: null,

    fieldName: '',

    markers: [],

    prevContent: '',

    initialContent: '',

    listen: {
        'layout:reposition mediator': 'adjustHeight'
    },

    validator: null,

    VALIDATE_TIMEOUT: 1000,

    renderProps: {},

    /**
     * @constructor
     * @param options
     */
    constructor: function ImportDialogView(options) {
        this.checkContentWithDelay = _.debounce(this.checkContent.bind(this), this.VALIDATE_TIMEOUT);
        ImportDialogView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     * @param options
     */
    initialize(options) {
        this.codeViewer = this.editor.CodeManager.getViewer('CodeMirror').clone();

        this.codeViewer.set(this.importViewerOptions);

        this.validateApiAccessor = new ApiAccessor(this.validateApiProps);
        this.twigResolverAccessor = new ApiAccessor(this.twigApiResolverProps);

        ImportDialogView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     * @returns {{modalImportButton: ImportDialogView.modalImportButton}}
     */
    getTemplateData() {
        return {
            modalImportButton: this.modalImportButton,
            modalExportButton: this.modalExportButton
        };
    },

    /**
     * @inheritdoc
     */
    render({
        content,
        dialogOptions = {},
        renderProps = {},
        exportButton = true
    } = {}) {
        this.renderProps = renderProps;
        ImportDialogView.__super__.render.call(this);

        this.content = unescapeTwigExpression(content ?? this.getImportContent());

        this.importButton = this.$el.find('[data-role="import"]');
        this.exportButton = this.$el.find('[data-role="export"]');

        if (!exportButton) {
            this.exportButton.hide();
        }

        this.dialog = new DialogWidget({
            autoRender: false,
            el: this.el,
            title: this.modalImportTitle,
            incrementalPosition: false,
            dialogOptions: {
                allowMaximize: true,
                autoResize: false,
                resizable: false,
                modal: true,
                height: 495,
                minWidth: 856,
                dialogClass: 'ui-dialog--import-template',
                close: () => {
                    this.editor.Commands.stop(this.commandId);
                }
            },
            ...dialogOptions
        });

        this.subview('loadingMask', new LoadingMaskView({
            container: this.dialog.loadingElement
        }));

        this.dialog.once('renderComplete', this.initCodeEditor.bind(this));
        this.dialog.render();
    },

    initCodeEditor() {
        this.codeViewer.init(this.$el.find('[data-role="code"]')[0]);
        this.viewerEditor = this.codeViewer.editor;

        this.codeViewer.setContent(stripRestrictedAttrs(this.content));
        this.viewerEditor.refresh();
        this.adjustHeight();
        this.checkContent(this.viewerEditor);
        this.bindEvents();

        this.initialContent = this.viewerEditor.getValue();
    },

    /**
     * Binding event listeners
     */
    bindEvents() {
        this.viewerEditor.on('changes', this.checkContentWithDelay);
        this.viewerEditor.on('blur', this.checkContentWithDelay);
        this.importButton.on('mouseover', this.checkContent.bind(this, this.viewerEditor));
        this.importButton.on('click', this.onImportCode.bind(this));
        this.exportButton.on('click', this.onExportCode.bind(this));
        this.dialog.widget.on('resize', this.adjustHeight.bind(this));
    },

    /**
     * Unbinding event listeners
     */
    unbindEvents() {
        this.viewerEditor.off();
        this.importButton.off();
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        if (this.commandId) {
            this.editor.Commands.stop(this.commandId);
        }

        this.unbindEvents();

        ImportDialogView.__super__.dispose.call(this);
    },

    closeDialog() {
        this.dialog.remove();
        delete this.dialog;

        if (this.editor.Commands.isActive(this.commandId)) {
            this.editor.Commands.stop(this.commandId);
        }

        this.prevContent = '';

        this.trigger('import:close');
    },

    /**
     * Get content for editor
     * @returns {string}
     */
    getImportContent() {
        return this.editor.getHtml() + '<style>' + this.editor.getCss() + '</style>';
    },

    /**
     * Check if content change
     */
    isChange() {
        return this.prevContent !== this.viewerEditor.getValue();
    },

    isInitialContentChanged() {
        return this.initialContent !== this.viewerEditor.getValue();
    },

    /**
     * Check content in editor
     * @param {Editor.Instance} codeEditor
     * @returns {number}
     */
    async checkContent(codeEditor) {
        if (!this.isChange()) {
            return true;
        }

        const messages = [];
        const {success, errors} = await this.validateContent();

        if (this.disposed) {
            return;
        }

        this.disabled = !success;
        this.importButton.attr('disabled', this.disabled);
        this.exportButton.attr('disabled', this.disabled);

        this.markers.forEach(marker => marker.clear());
        errors.forEach(({line, message}) => {
            this.markers.push(
                this.viewerEditor.markText(
                    {
                        line: line - 1,
                        ch: 0
                    },
                    {
                        line: line - 1,
                        ch: 1000
                    },
                    {
                        className: 'cm-error'
                    }
                )
            );
            messages.push(message);
        });

        this.validationMessage(messages.join('\n'));

        return success;
    },

    twigResolver(twigContent) {
        this.disabled = false;
        this.subview('loadingMask').show();
        this.importButton.attr('disabled', true);
        this.exportButton.attr('disabled', true);
        return this.twigResolverAccessor.send({}, {
            content: twigContent
        }).then(({content, success}) => {
            if (!success) {
                this.disabled = true;
                this.validationMessage(__('oro.cms.wysiwyg.import.message.twig_exp'));
            }
            this.importButton.attr('disabled', !success);
            this.exportButton.attr('disabled', !success);
            return content;
        }).catch(() => {
            this.validationMessage(__('oro.cms.wysiwyg.import.message.twig_exp'));
            this.disabled = true;
        }).always(() => {
            this.subview('loadingMask').hide();
        });
    },

    /**
     * Async validate content
     * @returns {Promise<{readonly errors?: *, readonly success?: *}>}
     */
    validateContent() {
        const content = this.viewerEditor.getValue();

        this.disabled = true;
        this.prevContent = content;
        this.importButton.attr('disabled', this.disabled);
        this.exportButton.attr('disabled', this.disabled);
        const errors = this.editor.CodeValidator.validate(
            content,
            this.renderProps.codeValidationOptions ?? {
                allowLock: false
            }
        );

        if (errors.length) {
            return {
                success: false,
                errors
            };
        }

        if (!this.editor.ComponentRestriction.allowTags.length) {
            return {
                success: true,
                errors: []
            };
        }

        return this.validateApiAccessor.send({}, {
            content: this.renderProps.noEscapeStyleTag ? content : content.replace(/<style>(.|\n)*?<\/style>/g, ''),
            className: this.entityClass,
            fieldName: this.fieldName
        }).then(({success, errors}) => {
            if (success) {
                this.editor.CodeValidator.restrictFaild = false;
            }
            return {success, errors: _.sortBy(errors, 'line')};
        });
    },

    /**
     * Remove excess style tags
     */
    clearStyleTags() {
        if (this.isolatedContentNode.find('style').length) {
            this.isolatedContentNode.find('style').remove();
            this.isolatedContent = this.isolatedContentNode.prop('outerHTML').trim();
        }
    },

    /**
     * Render validation message
     * @param message
     */
    validationMessage(message) {
        let vMessage = this.$el.find('.validation-failed');

        if (message) {
            if (!vMessage.length) {
                vMessage = $('<span />', {
                    'class': 'validation-failed'
                });

                vMessage.appendTo(this.$el);
                this.$el.addClass('has-message');
            }

            const {done, string} = messageCheck(message);
            done ? vMessage.html(string) : vMessage.text(message);
        } else {
            vMessage.remove();
            this.$el.removeClass('has-message');
        }

        this.adjustHeight();
    },

    showExportConfirmation() {
        this.removeSubview('exportConfirmation');
        this.subview('exportConfirmation', new StandartConfirmation({
            className: 'modal oro-modal-danger',
            title: __('oro.cms.wysiwyg.export.confirmation.title'),
            content: __('oro.cms.wysiwyg.export.confirmation.content'),
            okText: __('oro.cms.wysiwyg.export.confirmation.okText')
        }));

        return this.subview('exportConfirmation').open();
    },

    onExportCode() {
        const doExport = () => {
            const {Commands} = this.editor;

            if (Commands.has('gjs-export-zip')) {
                Commands.run('gjs-export-zip');
            }
        };

        if (this.isInitialContentChanged()) {
            this.showExportConfirmation().on('ok', async () => {
                await this.onImportCode();
                doExport();
            });
        } else {
            this.closeDialog();
            doExport();
        }
    },

    /**
     * Handle import content
     */
    async onImportCode() {
        const {editor} = this;
        let content = this.viewerEditor.getValue().trim();

        REGEXP_TWIG_TAGS.lastIndex = 0;
        if (REGEXP_TWIG_TAGS.test(content)) {
            content = await this.twigResolver(content);
        }

        if (!this.disabled) {
            const {html, css} = separateContent(content);
            const callback = this.renderProps.importCallback ?? this.importCallback;
            callback.call(this, {
                editor,
                html,
                css,
                content
            });
        }
    },

    importCallback({editor, html, css, content}) {
        editor.CssComposer.clear();
        editor.selectRemove(editor.getSelectedAll());
        editor.setComponents(escapeWrapper(html), {
            fromImport: true
        });
        editor.setStyle(editor.getPureStyleString(css));

        this.closeDialog();
        this.trigger('import:after', escapeWrapper(content));
    },

    /**
     * Adjust height code editor
     */
    adjustHeight() {
        if (!this.dialog) {
            return;
        }
        const height = this.$el.find('.validation-failed').height() || 0;
        this.viewerEditor.setSize(this.dialog.widget.width(), this.dialog.widget.height() - height);
        this.dialog.resetDialogPosition();
    }
});

export default ImportDialogView;
