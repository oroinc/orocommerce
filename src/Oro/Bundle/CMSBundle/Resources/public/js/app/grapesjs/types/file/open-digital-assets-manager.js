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
                const hasNonTextComponents = model.get('components')
                    .some(component => component.get('type') !== 'textnode');

                model.setAttributes({
                    href: url,
                    title: title,
                    target: target
                });

                if (hasNonTextComponents) {
                    return;
                }

                const textNodes = model.findType('textnode');
                if (!model.get('components').length) {
                    model.components([{
                        type: 'textnode',
                        content: title
                    }]);
                } else if (textNodes.length) {
                    textNodes[0].set('content', title);
                }

                if (traitText) {
                    traitText.setValue(title);
                }
            }
        }
    );
};

export default openDigitalAssetsManager;
