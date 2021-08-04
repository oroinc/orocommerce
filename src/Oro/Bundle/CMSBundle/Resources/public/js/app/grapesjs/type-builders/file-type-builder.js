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
                }).set('content', filename);

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

            toolbar.unshift({
                attributes: {
                    'class': 'fa fa-gear',
                    'label': __('oro.cms.wysiwyg.toolbar.fileSetting')
                },
                command: openDigitalAssetsManager.bind(null, this)
            });

            this.set('toolbar', toolbar);
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
        const {StyleManager} = this.editor;

        const DefaultPropertyType = StyleManager.getType('file');
        const DefaultView = DefaultPropertyType.view;
        const self = this;

        StyleManager.addType(
            'file',
            {
                view: DefaultView.extend({
                    init: function(...args) {
                        DefaultView.prototype.init.apply(this, args);
                    },

                    constructor: function DigitalAssetPropertyFileView(...args) {
                        DefaultView.prototype.constructor.apply(this, args);
                    },

                    /**
                     * @inheritdoc
                     */
                    openAssetManager: function() {
                        self.editor.Commands.run(
                            'open-digital-assets',
                            {
                                target: this.getTargetModel(),
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
                    _onSelect: function(digitalAssetModel) {
                        this.spreadUrl(digitalAssetModel.get('previewMetadata').url);
                    },

                    /**
                     * @inheritdoc
                     */
                    setValue: function(value, f) {
                        DefaultView.prototype.setValue.apply(this, [value, f]);
                    }
                })
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
