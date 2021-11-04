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
        initialize: function(options) {
            this.codeViewer = this.editor.CodeManager.getViewer('CodeMirror').clone();
            this.codeViewer.set(this.codeViewerOptions);
            this.content = _.unescape(this.editor.getSelected().getEl().innerHTML);
            CodeDialogView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         *
         * @returns {{modalCodeButton: CodeDialogView.modalCodeButton}}
         */
        getTemplateData: function() {
            return {
                modalCodeButton: this.modalCodeButton
            };
        },

        /**
         * @inheritdoc
         */
        render: function() {
            CodeDialogView.__super__.render.call(this);

            this.codeViewer.init(this.$el.find('[data-role="code"]')[0]);
            this.viewerEditor = this.codeViewer.editor;
            // Disable auto formatting text in the editor.
            this.viewerEditor.autoFormatRange = null;
            this.codeViewer.setContent(this.content);
            this.importButton = this.$el.find('[data-role="code-edit"]');

            this.dialog = new DialogWidget({
                autoRender: true,
                el: this.el,
                title: this.modalCodeTitle,
                loadingElement: this.editor.getEl(),
                dialogOptions: {
                    autoResize: false,
                    resizable: false,
                    modal: true,
                    height: 400,
                    minHeight: 435,
                    maxHeight: 435,
                    minWidth: 856,
                    maxWidth: 856,
                    appendTo: this.editor.getEl(),
                    dialogClass: 'ui-dialog--import-template',
                    close: _.bind(function() {
                        this.dispose();
                    }, this)
                }
            });

            this.viewerEditor.refresh();
            this.importButton.on('click', _.bind(this.onSave, this));
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.commandId) {
                this.editor.stopCommand(this.commandId);
            }

            this.viewerEditor.off('change');

            CodeDialogView.__super__.dispose.call(this);
        },

        onSave: function() {
            if (!this.disabled) {
                const codeContent = _.escape(this.viewerEditor.getValue().trim());
                this.editor.getSelected().components(codeContent);
                this.dialog.remove();
            }
        }
    });

    return CodeDialogView;
});
