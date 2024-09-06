import __ from 'orotranslation/js/translator';

const openDigitalAssetsManager = model => {
    model.em.get('Commands').run(
        'open-digital-assets',
        {
            target: model,
            title: __('oro.cms.wysiwyg.digital_asset.file.title'),
            routeName: 'oro_digital_asset_widget_choose_file',
            onSelect(digitalAssetModel) {
                const {
                    url,
                    title = '',
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
                    content: title
                }]);

                if (traitText) {
                    traitText.set('value', title);
                }
            }
        }
    );
};

export default openDigitalAssetsManager;
