define(function(require) {
    'use strict';

    var DigitalAssetPropertyFileTypeComponent;
    var __ = require('orotranslation/js/translator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DigitalAssetHelper = require('orocms/js/app/grapesjs/helpers/digital-asset-helper');

    /**
     * Overrides "file" property type in StyleManager to enable digital asset manager when choosing background image.
     */
    DigitalAssetPropertyFileTypeComponent = BaseComponent.extend({
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
            var DefaultPropertyType = this.editor.StyleManager.getType('file');
            var DefaultView = DefaultPropertyType.view;
            var digitalAssetsComponent = this;

            this.editor.StyleManager.addType(
                'file',
                {
                    view: DefaultView.extend({
                        constructor: function DigitalAssetPropertyFileView() {
                            DefaultView.prototype.constructor.apply(this, arguments);
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
                            var metadata = digitalAssetModel.get('previewMetadata');

                            this.spreadUrl(
                                '{{ wysiwyg_image(' + metadata['digitalAssetId'] + ',"' + metadata['uuid'] + '") }}'
                            );
                        },

                        /**
                         * @inheritDoc
                         */
                        setValue: function(value, f) {
                            value = DigitalAssetHelper.getImageUrlFromTwigTag(this.model.get('src'));

                            DefaultView.prototype.setValue.apply(this, [value, f]);
                        }
                    })
                }
            );
        }
    });

    return DigitalAssetPropertyFileTypeComponent;
});
