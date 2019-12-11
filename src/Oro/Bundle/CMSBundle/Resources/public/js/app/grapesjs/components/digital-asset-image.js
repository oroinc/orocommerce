define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const DigitalAssetHelper = require('orocms/js/app/grapesjs/helpers/digital-asset-helper');

    /**
     * Overrides "image" dom component type to make it use digital assets manager.
     */
    const DigitalAssetImageComponent = BaseComponent.extend({
        editor: null,

        constructor: function DigitalAssetImageComponent(editor, options) {
            this.editor = editor;

            DigitalAssetImageComponent.__super__.constructor.apply(this, [options || []]);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this._addBlock();
            this._addComponentType();
        },

        /**
         * Overrides "image" block
         * @private
         */
        _addBlock: function() {
            if (this.editor.ComponentRestriction.isAllow([
                'img'
            ])) {
                this.editor.BlockManager.add('image', {
                    label: __('oro.cms.wysiwyg.component.digital_asset.image'),
                    attributes: {
                        'class': 'fa fa-picture-o'
                    }
                });
            }
        },

        /**
         * @param {DigitalAssetImageComponentModel} digitalAssetImageComponentModel
         * @private
         */
        _openDigitalAssetManager: function(digitalAssetImageComponentModel) {
            this.editor.Commands.run(
                'open-digital-assets',
                {
                    target: digitalAssetImageComponentModel,
                    title: __('oro.cms.wysiwyg.digital_asset.image.title'),
                    routeName: 'oro_digital_asset_widget_choose_image',
                    onSelect: function(digitalAssetModel) {
                        const {digitalAssetId, uuid, title} = digitalAssetModel.get('previewMetadata');

                        digitalAssetImageComponentModel
                            .setAttributes({alt: title || ''})
                            .set(
                                'src',
                                `{{ wysiwyg_image('${digitalAssetId}','${uuid}') }}`
                            );
                    }
                }
            );
        },

        /**
         * Overrides "image" dom component type
         * @private
         */
        _addComponentType: function() {
            const DefaultComponentType = this.editor.DomComponents.getType('image');
            const DefaultModel = DefaultComponentType.model;
            const DefaultView = DefaultComponentType.view;
            const self = this;

            self.editor.DomComponents.addType('image', {
                model: DefaultModel,

                view: DefaultView.extend({
                    constructor: function DigitalAssetImageComponentView(...args) {
                        DefaultView.prototype.constructor.apply(this, args);
                    },

                    /**
                     * @inheritDoc
                     */
                    openModal: function(e) {
                        e && e.stopPropagation();

                        if (self.editor && this.model.get('editable')) {
                            self._openDigitalAssetManager(this.model);
                        }
                    },

                    /**
                     * @inheritDoc
                     */
                    updateAttributes: function(...args) {
                        DefaultView.prototype.updateAttributes.apply(this, args);

                        const imageSrc = DigitalAssetHelper.getImageUrlFromTwigTag(this.model.get('src'));
                        if (imageSrc) {
                            this.$el.attr('src', imageSrc);
                        }
                    }
                })

            });
        }
    });

    return DigitalAssetImageComponent;
});
