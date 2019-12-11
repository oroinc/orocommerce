define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const DigitalAssetHelper = require('orocms/js/app/grapesjs/helpers/digital-asset-helper');

    /**
     * Overrides "file" property type in StyleManager to enable digital asset manager when choosing background image.
     */
    const DigitalAssetPropertyFileTypeComponent = BaseComponent.extend({
        editor: null,

        constructor: function DigitalAssetPropertyFileTypeComponent(editor, options) {
            this.editor = editor;

            DigitalAssetPropertyFileTypeComponent.__super__.constructor.apply(this, [options || []]);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this._overrideFileType();
        },

        /**
         * Overrides "file" property type
         * @private
         */
        _overrideFileType: function() {
            const {StyleManager} = this.editor;

            const DefaultPropertyType = StyleManager.getType('file');
            const DefaultView = DefaultPropertyType.view;
            const digitalAssetsComponent = this;

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
                         * @inheritDoc
                         */
                        openAssetManager: function() {
                            digitalAssetsComponent.editor.Commands.run(
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
                         * @param {OpenDigitalAssetsCommand} command
                         * @private
                         */
                        _onSelect: function(digitalAssetModel, command) {
                            const {digitalAssetId, uuid} = digitalAssetModel.get('previewMetadata');

                            this.spreadUrl(
                                `"{{ wysiwyg_image('${digitalAssetId}','${uuid}') }}"`
                            );
                        },

                        /**
                         * @inheritDoc
                         */
                        setValue: function(value, f) {
                            value = DigitalAssetHelper.getImageUrlFromTwigTag(value);

                            DefaultView.prototype.setValue.apply(this, [value, f]);
                        }
                    })
                }
            );
        }
    });

    return DigitalAssetPropertyFileTypeComponent;
});
