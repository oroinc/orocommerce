import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import template from 'tpl-loader!orocms/templates/controls/picture-settings/densities/densities-collection-view.html';
import DensitiesCollection from './densities-collection';
import DensitiesItemView from './densities-item-view';

const DensitiesCollectionView = BaseCollectionView.extend({
    optionNames: BaseCollectionView.prototype.optionNames.concat(['editor', 'dialog', 'alwaysAddInitialEmpty']),

    tagName: 'tr',

    className: 'additional-srcset-collection',

    template,

    itemView: DensitiesItemView,

    alwaysAddInitialEmpty: false,

    listSelector: '[data-role="list"]',

    events: {
        'click [data-role="add-srcset"]': 'onClickAdd'
    },

    listen: {
        'change:attributes model': 'changeSourceModel'
    },

    constructor: function DensitiesCollectionView(...args) {
        DensitiesCollectionView.__super__.constructor.apply(this, args);
    },

    filterer(item) {
        return !item.get('origin');
    },

    initialize(options) {
        if (!options.collection) {
            this.collection = new DensitiesCollection(this.resolveCollectionData(), {
                mimeType: this.model.getMimeType()
            });

            this.model.set('densities', this.collection);
        }

        if (options.alwaysAddInitialEmpty) {
            this.collection.addEmptyItem();
        }

        DensitiesCollectionView.__super__.initialize.call(this, options);
    },

    resolveCollectionData() {
        if (this.model.get('attributes').srcset) {
            return DensitiesCollection.parseSrcSet(this.model.get('attributes').srcset);
        }

        if (this.model.get('attributes').src) {
            return DensitiesCollection.parseSrcSet(this.model.get('attributes').src);
        }

        return [];
    },

    render() {
        DensitiesCollectionView.__super__.render.call(this);

        this.$('[data-toggle="tooltip"]').tooltip();
        this.addItemsBtnVisibility();

        return this;
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
                collection: this.collection,
                model
            });
        } else {
            throw new Error(
                'The CollectionView#itemView property must be defined or the initItemView() must be overridden.'
            );
        }
    },

    onClickAdd(event) {
        event.preventDefault();

        if (this.collection.getAvailableOptions().length) {
            this.collection.add({
                density: this.collection.getDensityForNew()
            });
        }
    },

    itemAdded(...args) {
        const res = DensitiesCollectionView.__super__.itemAdded.apply(this, args);
        this.dialog.resetDialogPosition();

        this.addItemsBtnVisibility();

        this.collection.sort();

        return res;
    },

    itemRemoved(...args) {
        const res = DensitiesCollectionView.__super__.itemRemoved.apply(this, args);
        this.dialog.resetDialogPosition();

        this.addItemsBtnVisibility();

        return res;
    },

    addItemsBtnVisibility() {
        this.$('[data-role="srcset-controls"]').toggleClass('hide', !this.collection.getAvailableOptions().length);
    },

    changeSourceModel(model) {
        if (!model.get('densities')) {
            return;
        }

        const origin = model.get('densities').getOrigin();

        if (origin && (model.getAttribute('srcset') || model.getAttribute('src'))) {
            origin.updateImageUrl(model.getAttribute('srcset') || model.getAttribute('src'));
        }
    }
});

export default DensitiesCollectionView;
