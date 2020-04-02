define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!orocms/templates/grapesjs-import-dialog-template.html');
    const DialogWidget = require('oro/dialog-widget');
    const {stripRestrictedAttrs} = require('orocms/js/app/grapesjs/plugins/grapesjs-style-isolation');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');

    const ImportDialogView = BaseView.extend({
        /**
         * @inheritDoc
         */
        optionNames: BaseView.prototype.optionNames.concat([
            'editor', 'importViewerOptions',
            'modalImportLabel', 'modalImportTitle', 'modalImportButton'
        ]),

        /**
         * @inheritDoc
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
         * @property {Object}
         */
        dialog: null,

        /**
         * @property {Object}
         */
        dialogOptions: {

        },

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
        constructor: function ImportDialogView(options) {
            ImportDialogView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         * @param options
         */
        initialize: function(options) {
            this.codeViewer = this.editor.CodeManager.getViewer('CodeMirror').clone();

            this.codeViewer.set(this.importViewerOptions);

            this.content = this.getImportContent();

            ImportDialogView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         * @returns {{modalImportButton: ImportDialogView.modalImportButton}}
         */
        getTemplateData: function() {
            return {
                modalImportButton: this.modalImportButton
            };
        },

        /**
         * @inheritDoc
         */
        render: function() {
            ImportDialogView.__super__.render.call(this);

            this.codeViewer.init(this.$el.find('[data-role="code"]')[0]);
            this.viewerEditor = this.codeViewer.editor;

            this.codeViewer.setContent(stripRestrictedAttrs(this.content));

            this.importButton = this.$el.find('[data-role="import"]');

            this.dialog = new DialogWidget({
                autoRender: true,
                el: this.el,
                title: this.modalImportTitle,
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

            if (this.editor.ComponentRestriction.allowTags) {
                this.checkContent(this.viewerEditor);
            }

            this.bindEvents();
        },

        /**
         * Binding event listeners
         */
        bindEvents: function() {
            if (this.editor.ComponentRestriction.allowTags) {
                this.viewerEditor.on('change', _.throttle(_.bind(this.checkContent, this), 500));
                this.viewerEditor.on('blur', _.bind(this.checkContent, this));
            }

            this.importButton.on('click', _.bind(this.onImportCode, this));
        },

        /**
         * Unbinding event listeners
         */
        unbindEvents: function() {
            this.viewerEditor.off('change');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.commandId) {
                this.editor.stopCommand(this.commandId);
            }

            this.unbindEvents();

            ImportDialogView.__super__.dispose.call(this);
        },

        /**
         * Get content for editor
         * @returns {string}
         */
        getImportContent: function() {
            return this.editor.getHtml() + '<style>' + this.editor.getCss() + '</style>';
        },

        /**
         * Check content in editor
         * @param {Editor.Instance} codeEditor
         * @returns {number}
         */
        checkContent: function(codeEditor) {
            this.content = codeEditor.getValue().trim();
            this.isolatedContent = this.editor.getIsolatedHtml(this.content);
            this.isolatedContentNode = $(this.isolatedContent);
            this.clearStyleTags();

            const _res = this.content === ''
                ? []
                : this.editor.ComponentRestriction.validate(this.isolatedContent, true);
            const validationMessage = __('oro.cms.wysiwyg.validation.import', {tags: _res.join(', ')});

            this.validationMessage(_res.length ? validationMessage : false);

            this.disabled = !!_res.length;

            this.importButton.attr('disabled', !!_res.length);

            return _res.length;
        },

        /**
         * Remove excess style tags
         */
        clearStyleTags: function() {
            if (this.isolatedContentNode.find('style').length) {
                this.isolatedContentNode.find('style').remove();
                this.isolatedContent = this.isolatedContentNode.prop('outerHTML').trim();
            }
        },

        /**
         * Render validation message
         * @param message
         */
        validationMessage: function(message) {
            const vMessage = this.$el.find('.validation-failed');

            if (message) {
                if (vMessage.length) {
                    vMessage.text(message);
                } else {
                    this.$el.append($('<span />', {
                        'class': 'validation-failed'
                    }).text(message));
                    this.$el.addClass('has-message');
                }
            } else {
                vMessage.remove();
                this.$el.removeClass('has-message');
            }
        },

        /**
         * Handle import content
         */
        onImportCode: function() {
            if (!this.disabled) {
                this.editor.setComponents(this.viewerEditor.getValue().trim());
                this.dialog.remove();
            }
        }
    });

    return ImportDialogView;
});
