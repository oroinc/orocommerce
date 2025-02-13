import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orocms/templates/controls/picture-settings/densities/densities-item-view.html';
import DeleteConfirmation from 'oroui/js/delete-confirmation';

const DensitiesItemView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['editor', 'dialog']),

    events() {
        const events = {};

        if (!this.model.get('origin')) {
            Object.assign(events, {
                'click .removeRow': 'removeItem',
                'click [data-role="upload"]': 'updateSourceImage',
                'click .editRow': 'updateSourceImage',
                'click [data-role="density-option"]': 'onChangeDensity'
            });
        }

        return events;
    },

    listen: {
        'change model': 'render',
        'add collection': 'render',
        'remove collection': 'render',
        'change:density collection': 'render'
    },

    tagName: 'tr',

    className() {
        const classes = ['exclude'];

        if (this.model.get('origin')) {
            classes.push('disabled');
        }

        return classes.join(' ');
    },

    template,

    constructor: function DensitiesItemView(...args) {
        DensitiesItemView.__super__.constructor.apply(this, args);
    },

    updateSourceImage() {
        const {Commands} = this.editor;
        const {model} = this;

        Commands.run(
            'open-digital-assets',
            {
                title: __('oro.cms.wysiwyg.digital_asset.image.title'),
                routeName: 'oro_digital_asset_widget_choose_image',
                loadingElement: this.dialog.loadingElement,
                onSelect(digitalAssetModel) {
                    model.updateImageUrl(
                        digitalAssetModel.get('previewMetadata')[
                            model.collection.mimeType === 'image/webp' ? 'url_webp' : 'url'
                        ]
                    );
                }
            }
        );
    },

    removeItem() {
        if (!this.model.get('url')) {
            return this.model.collection.removeItem(this.model);
        }

        const confirm = new DeleteConfirmation({
            content: __('oro.cms.wysiwyg.dialog.picture_settings.remove_confirmation')
        });
        confirm.on('ok', () => this.model.collection.removeItem(this.model));
        confirm.open();
    },

    onChangeDensity({currentTarget}) {
        this.model.set('density', parseInt(currentTarget.dataset.value));
    }
});

export default DensitiesItemView;
