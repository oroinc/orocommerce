import __ from 'orotranslation/js/translator';

export default BaseTypeView => {
    const ImageTypeView = BaseTypeView.extend({
        constructor: function ImageTypeView(...args) {
            return ImageTypeView.__super__.constructor.apply(this, args);
        },

        onActive(e) {
            if (e) {
                e.stopPropagation();
            }

            this.model.set('isNew', !e);

            if (this.model.get('editable')) {
                this._openDigitalAssetManager(this.model);
            }
        },

        _openDigitalAssetManager(digitalAssetImageComponentModel) {
            this.em.get('Commands').run(
                'open-digital-assets',
                {
                    target: digitalAssetImageComponentModel,
                    title: __('oro.cms.wysiwyg.digital_asset.image.title'),
                    routeName: 'oro_digital_asset_widget_choose_image',
                    onSelect: function(digitalAssetModel) {
                        digitalAssetImageComponentModel.set(
                            'previewMetadata',
                            digitalAssetModel.get('previewMetadata')
                        );
                        const {url, title} = digitalAssetImageComponentModel.get('previewMetadata');

                        digitalAssetImageComponentModel.set('src', url).addAttributes({
                            alt: title || ''
                        });

                        digitalAssetImageComponentModel.set('isNew', false);
                    },
                    onClose() {
                        if (digitalAssetImageComponentModel.get('isNew')) {
                            digitalAssetImageComponentModel.em.get('Editor').runCommand('tlb-delete');
                        }
                    }
                }
            );
        },

        onError(...args) {
            ImageTypeView.__super__.onError.apply(this, args);

            const parent = this.el.parentNode;
            if (parent.tagName === 'PICTURE') {
                parent.querySelectorAll('source').forEach(child => child.srcset = '');
            }
        }
    });

    return ImageTypeView;
};
