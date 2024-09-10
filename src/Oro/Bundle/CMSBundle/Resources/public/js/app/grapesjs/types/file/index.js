import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import openDigitalAssetsCommand from 'orocms/js/app/grapesjs/modules/open-digital-assets-command';
import openDigitalAssetsManager from './open-digital-assets-manager';
import TypeModel from './file-type-model';

const FileType = BaseType.extend({
    parentType: 'link',

    button: {
        id: 'file',
        label: __('oro.cms.wysiwyg.component.digital_asset.file'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-file-o'
        },
        activate: true
    },

    TypeModel,

    viewProps: {
        tagName: 'a',

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

    constructor: function FileType(options) {
        FileType.__super__.constructor.call(this, options);
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

        FileType.__super__.createPanelButton.call(this);
    },

    registerEditorCommands() {
        if (this.editor.Commands.has('open-digital-assets')) {
            return;
        }

        FileType.__super__.registerEditorCommands.call(this);
    },

    isComponent: function(el) {
        return el.nodeType === el.ELEMENT_NODE &&
            el.tagName === 'A' &&
            el.classList.contains('digital-asset-file');
    }
}, {
    type: 'file'
});

export default FileType;
