define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!orocms/templates/grapesjs-code-block.html');
    const DialogWidget = require('oro/dialog-widget');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');

    const CodeDialogView = BaseView.extend({
        /**
         * @inheritdoc
         */
        optionNames: BaseView.prototype.optionNames.concat([
            'editor', 'codeViewerOptions',
            'modalCodeLabel', 'modalCodeTitle', 'modalCodeButton'
        ]),

        /**
         * @inheritdoc
         */
        autoRender: true,

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
        codeViewerOptions: {
            codeName: null,
            theme: 'hopscotch',
            readOnly: 0
        },

        /**
         * @property {String}
         */
        modalCodeLabel: __('oro.cms.wysiwyg.code.label'),

        /**
         * @property {String}
         */
        modalCodeTitle: __('oro.cms.wysiwyg.code.title'),

        /**
         * @property {String}
         */
        modalCodeButton: __('oro.cms.wysiwyg.code.button'),

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

        /**
         * @constructor
         * @param options
         */
        constructor: function CodeDialogView(options) {
            CodeDialogView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         * @param options
         */
        initialize(options) {
            this.codeViewer = this.editor.CodeManager.getViewer('CodeMirror').clone();
            this.codeViewer.set(this.codeViewerOptions);
            this.content = _.unescape(this.editor.getSelected().getContent());
            CodeDialogView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         *
         * @returns {{modalCodeButton: CodeDialogView.modalCodeButton}}
         */
        getTemplateData() {
            return {
                modalCodeButton: this.modalCodeButton
            };
        },

        /**
         * @inheritdoc
         */
        render() {
            CodeDialogView.__super__.render.call(this);

            this.importButton = this.$el.find('[data-role="code-edit"]');

            this.dialog = new DialogWidget({
                autoRender: false,
                el: this.el,
                title: this.modalCodeTitle,
                incrementalPosition: false,
                dialogOptions: {
                    autoResize: false,
                    resizable: false,
                    modal: true,
                    height: 495,
                    minWidth: 856,
                    dialogClass: 'ui-dialog--import-template',
                    close: () => {
                        this.dispose();
                    }
                }
            });

            this.dialog.once('renderComplete', this.initCodeEditor.bind(this));
            this.dialog.render();
            this.importButton.on('click', this.onSave.bind(this));
        },

        initCodeEditor() {
            this.codeViewer.init(this.$el.find('[data-role="code"]')[0]);
            this.viewerEditor = this.codeViewer.editor;
            // Disable auto formatting text in the editor.
            this.viewerEditor.autoFormatRange = null;
            this.codeViewer.setContent(this.content);
            this.viewerEditor.refresh();
            this.adjustHeight();
        },

        /**
         * @inheritdoc
         */
        dispose() {
            if (this.disposed) {
                return;
            }

            if (this.commandId) {
                this.editor.stopCommand(this.commandId);
            }

            this.viewerEditor.off('change');

            CodeDialogView.__super__.dispose.call(this);
        },

        onSave() {
            if (!this.disabled) {
                const codeContent = _.escape(this.viewerEditor.getValue().trim());
                this.editor.getSelected().setContent(codeContent);
                this.dialog.remove();
            }
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

    return CodeDialogView;
});
