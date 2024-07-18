import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import IconsCollection from './collection';
import IconItemView from './icon-item-view';
import template from 'tpl-loader!orocms/templates/controls/icon-settings/icon-settings.html';

const IconsCollectionView = BaseCollectionView.extend({
    optionNames: BaseCollectionView.prototype.optionNames.concat(
        ['traitMode', 'searchFieldCls', 'showLabel', 'editor']
    ),

    autoRender: true,

    traitMode: false,

    showLabel: true,

    searchFieldCls: 'search-field',

    template,

    className: 'icons-settings',

    itemView: IconItemView,

    useCssAnimation: true,

    listSelector: '[data-icon-collection]',

    loadingContainerSelector: '[data-icon-collection]',

    events: {
        'input [name="search"]': 'filter',
        'change': 'onChange'
    },

    listen: {
        'change:selected collection': 'onSelected'
    },

    constructor: function IconsCollectionView(...args) {
        IconsCollectionView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.collection = new IconsCollection();

        IconsCollectionView.__super__.initialize.call(this, options);
    },

    delegateListeners() {
        this.listenTo(this.editor.em, 'changeTheme', this.fetchAndSetIconsData.bind(this));
        IconsCollectionView.__super__.delegateListeners.call(this);
    },

    getTemplateData() {
        const data = IconsCollectionView.__super__.getTemplateData.call(this);

        data.traitMode = this.traitMode;
        data.searchFieldCls = this.searchFieldCls;

        return data;
    },

    render() {
        if (this.traitMode) {
            this.$el.addClass(`${this.className} trait-mode`);
        }

        IconsCollectionView.__super__.render.call(this);

        this.$noMatches = this.$('.no-data');
        this.$noMatches.detach();

        this.initLoadingIndicator();
        this.fetchAndSetIconsData();
    },

    fetchAndSetIconsData() {
        this.collection.beginSync();
        this.toggleLoadingIndicator();
        const theme = this.editor.em.get('currentTheme');

        if (theme.svgIconsSupport === false) {
            this.collection.reset();
            this.collection.finishSync();
            this.$el.trigger('content:changed');
            return;
        }

        this.editor.IconsService.getAndParseIconCollection({theme}).then(data => {
            this.collection.set(data.map(item => {
                return {
                    ...item,
                    showLabel: this.showLabel
                };
            }), {
                merge: true
            });

            this.collection.finishSync();
            this.$el.trigger('content:changed');
        });
    },

    getValue() {
        const found = this.collection.getSelected();
        return found ? found.get('id') : '';
    },

    setValue(id) {
        this.collection.setSelected(id);
    },

    onChange() {
        this.collection.setSelected(this.el.value);
    },

    filter(...args) {
        IconsCollectionView.__super__.filter.apply(this, args);
        this.toggleNotFoundMessage(!this.visibleItems.length);
    },

    filterer(item) {
        return item.get('id').includes(this.$('[name="search"]').val());
    },

    filterCallback: function(view, included) {
        view.$el.toggleClass('show', included);
    },

    toggleNotFoundMessage(toggle) {
        if (toggle) {
            this.$noMatches.appendTo(this.$list);
        } else {
            this.$noMatches.detach();
        }
    },

    onSelected(model, selected) {
        if (selected) {
            this.trigger('selected', model.id);
        }
    }
});

export default IconsCollectionView;
