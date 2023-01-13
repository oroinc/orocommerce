import __ from 'orotranslation/js/translator';
import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import PictureSettingsCollection from './picture-settings-collection';
import template from 'tpl-loader!orocms/templates/controls/picture-settings/picture-settings-collection.html';
import PictureSettingsItemView from './picture-settings-item-view';
import layout from 'oroui/js/layout';

const PictureSettingsCollectionView = BaseCollectionView.extend({
    optionNames: BaseCollectionView.prototype.optionNames.concat(['editor', 'dialog']),

    editor: null,

    dialog: null,

    template,

    itemView: PictureSettingsItemView,

    listSelector: 'tbody',

    events: {
        'click [data-action="add-source"]': 'addSource',
        'sortupdate .sortable-wrapper': 'onSort'
    },

    constructor: function PictureSettingsCollectionView(...args) {
        PictureSettingsCollectionView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.collection = new PictureSettingsCollection([
            ...options.sources,
            {
                ...options.mainImage,
                main: true
            }
        ]);

        PictureSettingsCollectionView.__super__.initialize.call(this, options);
    },

    /**
     * Rewrite method to put options for itemView
     * @param model
     * @returns {*}
     */
    initItemView(model) {
        if (this.itemView) {
            return new this.itemView({
                editor: this.editor,
                dialog: this.dialog,
                model
            });
        } else {
            throw new Error(
                'The CollectionView#itemView property must be defined or the initItemView() must be overridden.'
            );
        }
    },

    render() {
        PictureSettingsCollectionView.__super__.render.call(this);

        layout.initPopover(this.$el);
    },

    removeViewForItem(model) {
        PictureSettingsCollectionView.__super__.removeViewForItem.call(this, model);
        this.collection.updateSortable();
    },

    renderAllItems() {
        PictureSettingsCollectionView.__super__.renderAllItems.call(this);

        this.$('.sortable-wrapper').sortable({
            tolerance: 'pointer',
            delay: 100,
            opacity: 0.75,
            containment: 'parent',
            items: 'tr:not(.exclude)',
            handle: '[data-name="sortable-handle"]'
        });
        this.collection.updateSortable();
    },

    addSource() {
        const {Commands} = this.editor;
        const {collection} = this;
        Commands.run(
            'open-digital-assets',
            {
                title: __('oro.cms.wysiwyg.digital_asset.image.title'),
                routeName: 'oro_digital_asset_widget_choose_image',
                loadingElement: this.dialog.loadingElement,
                onSelect(digitalAssetModel) {
                    collection.add({
                        attributes: {
                            srcset: digitalAssetModel.get('previewMetadata').url,
                            type: digitalAssetModel.get('mimeType')
                        },
                        index: collection.length - 1
                    });

                    collection.sort();
                }
            }
        );
    },

    onSort() {
        Object.values(this.getItemViews()).forEach(
            view => !view.model.get('main') && view.model.set('index', view.$el.index())
        );
        this.collection.sort();
    },

    getData() {
        return this.collection.getData();
    },

    toHTML() {
        const sources = this.collection.reduce((str, model) => {
            const subview = this.getItemView(model);
            str += subview.toHTML() + '\n';
            return str;
        }, '');
        return `<picture>${sources}</picture>`;
    }
});

export default PictureSettingsCollectionView;
