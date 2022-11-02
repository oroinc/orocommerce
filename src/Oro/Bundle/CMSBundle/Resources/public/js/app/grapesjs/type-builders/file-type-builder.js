import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';
import openDigitalAssetsCommand from 'orocms/js/app/grapesjs/modules/open-digital-assets-command';

function openDigitalAssetsManager(model) {
    model.em.get('Commands').run(
        'open-digital-assets',
        {
            target: model,
            title: __('oro.cms.wysiwyg.digital_asset.file.title'),
            routeName: 'oro_digital_asset_widget_choose_file',
            onSelect: function(digitalAssetModel) {
                const {
                    url,
                    title = '',
                    filename = '',
                    target = '_self'
                } = digitalAssetModel.get('previewMetadata');
                const traitText = model.getTrait('text');

                model.setAttributes({
                    href: url,
                    title: title,
                    target: target
                });

                model.components([{
                    type: 'textnode',
                    content: filename
                }]);

                if (traitText) {
                    traitText.set('value', filename);
                }
            }
        }
    );
}

const FileTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'link',

    button: {
        id: 'file',
        label: __('oro.cms.wysiwyg.component.digital_asset.file'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-file-o'
        }
    },

    modelMixin: {
        defaults: {
            'type': 'file',
            'tagName': 'a',
            'classes': ['digital-asset-file', 'no-hash'],
            'activeOnRender': 1,
            'void': 0,
            'droppable': 1,
            'editable': 1,
            'highlightable': 0,
            'resizable': 0,
            'traits': ['title', 'text', 'target']
        },

        initialize: function(...args) {
            this.constructor.__super__.initialize.apply(this, args);

            const toolbar = this.get('toolbar');
            if (!toolbar.find(toolbar => toolbar.id === 'file-settings')) {
                this.set('toolbar', [
                    {
                        attributes: {
                            'class': 'fa fa-gear',
                            'label': __('oro.cms.wysiwyg.toolbar.fileSetting')
                        },
                        id: 'file-settings',
                        command(editor) {
                            const selected = editor.getSelected();
                            if (selected) {
                                openDigitalAssetsManager(selected);
                            }
                        }
                    },
                    ...toolbar
                ]);
            }
        },

        /**
         * Returns object of attributes for HTML
         * @return {Object}
         * @private
         */
        getAttrToHTML: function(...args) {
            const attr = this.constructor.__super__.getAttrToHTML.apply(this, args);

            _.each(['href', 'title'], (function(attributeName) {
                const attributeValue = this.get(attributeName);
                if (attributeValue) {
                    attr[attributeName] = attributeValue;
                }
            }).bind(this));

            return attr;
        }
    },

    viewMixin: {
        tagName: 'a',

        events: {
            dblclick: 'onActive'
        },

        onActive: function(e) {
            e && e.stopPropagation();

            this.openModal();
        },

        /**
         * Opens dialog for file changing
         * @private
         */
        openModal: function() {
            openDigitalAssetsManager(this.model);
        }
    },

    commands: {
        'open-digital-assets': openDigitalAssetsCommand
    },

    constructor: function FileTypeBuilder(options) {
        FileTypeBuilder.__super__.constructor.call(this, options);
    },

    onInit: function() {
        this.editor.StyleManager.addType(
            'file',
            {
                openAssetManager() {
                    this.em.get('Commands').run(
                        'open-digital-assets',
                        {
                            target: this.model,
                            title: __('oro.cms.wysiwyg.digital_asset.image.title'),
                            routeName: 'oro_digital_asset_widget_choose_image',
                            onSelect: this._onSelect.bind(this)
                        }
                    );
                },

                /**
                 * @param {Backbone.Model} digitalAssetModel
                 * @private
                 */
                _onSelect(digitalAssetModel) {
                    this.model.upValue(digitalAssetModel.get('previewMetadata').url);
                }
            }
        );
    },

    createPanelButton() {
        if (!this.editor.ComponentRestriction.isAllow(['a'])) {
            return;
        }

        FileTypeBuilder.__super__.createPanelButton.call(this);
    },

    registerEditorCommands() {
        if (this.editor.Commands.has('open-digital-assets')) {
            return;
        }

        FileTypeBuilder.__super__.registerEditorCommands.call(this);
    },

    isComponent: function(el) {
        let result = null;

        if (el.tagName === 'A' && el.classList.contains('digital-asset-file')) {
            result = {
                type: 'file'
            };
        }

        return result;
    }
});

export default FileTypeBuilder;
