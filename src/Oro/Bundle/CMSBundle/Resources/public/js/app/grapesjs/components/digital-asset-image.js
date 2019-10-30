define(function(require) {
    'use strict';

    var DigitalAssetImageComponent;
    var __ = require('orotranslation/js/translator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DigitalAssetHelper = require('orocms/js/app/grapesjs/helpers/digital-asset-helper');

    /**
     * Overrides "image" dom component type to make it use digital assets manager.
     */
    DigitalAssetImageComponent = BaseComponent.extend({
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
            this.editor.BlockManager.add('image', {
                label: __('oro.cms.wysiwyg.component.digital_asset.image'),
                attributes: {
                    'class': 'fa fa-picture-o'
                }
            });
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
                        var metadata = digitalAssetModel.get('previewMetadata');

                        digitalAssetImageComponentModel
                            .setAttributes({alt: metadata['title'] || ''})
                            .set(
                                'src',
                                '{{ wysiwyg_image(' + metadata['digitalAssetId'] + ',"' + metadata['uuid'] + '") }}'
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
            var DefaultComponentType = this.editor.DomComponents.getType('image');
            var DefaultModel = DefaultComponentType.model;
            var DefaultView = DefaultComponentType.view;
            var self = this;

            self.editor.DomComponents.addType('image', {
                model: DefaultModel,

                view: DefaultView.extend({
                    constructor: function DigitalAssetImageComponentView() {
                        DefaultView.prototype.constructor.apply(this, arguments);
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
                    updateAttributes: function() {
                        DefaultView.prototype.updateAttributes.apply(this, arguments);

                        this.$el.attr('src', DigitalAssetHelper.getImageUrlFromTwigTag(this.model.get('src')));
                    }
                })

            });
        }
    });

    return DigitalAssetImageComponent;
});
