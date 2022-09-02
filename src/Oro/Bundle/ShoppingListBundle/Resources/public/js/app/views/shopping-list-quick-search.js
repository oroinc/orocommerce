import {extend} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import DropdownSearch from 'orofrontend/default/js/app/views/dropdown-search';

const ShoppingListQuickSearch = BaseView.extend(extend({}, ElementsHelper, {
    optionNames: BaseView.prototype.optionNames.concat([
        'minimumResultsForSearch'
    ]),

    dropdownWidget: null,

    /**
     * @param {Number}
     */
    minimumResultsForSearch: 5,

    /**
     * @inheritdoc
     */
    constructor: function ShoppingListQuickSearch(options) {
        ShoppingListQuickSearch.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        ShoppingListQuickSearch.__super__.initialize.call(this, options);
        this.deferredInitializeCheck(options, ['productModel', 'dropdownWidget']);
    },

    /**
     * @inheritdoc
     * @param options
     */
    deferredInitialize(options) {
        this.dropdownWidget = options.dropdownWidget;
    },

    /**
     * Render search field
     * @private
     */
    _afterRenderButtons() {
        const $dropdown = this.dropdownWidget.dropdown;

        if (!$dropdown || $dropdown.find('.items-group > li[role="menuitem"]').length <= this.minimumResultsForSearch) {
            return;
        }

        this.$el.removeClass('hide');
        const dropdownSearch = new DropdownSearch({
            minimumResultsForSearch: this.minimumResultsForSearch,
            el: this.dropdownWidget.dropdown,
            searchContainerSelector: '[data-intention="search"]'
        });

        this.subview('dropdown-search', dropdownSearch);

        dropdownSearch.clearField(true);

        this.dropdownWidget.group.on('show.bs.dropdown', e => {
            if (!dropdownSearch.disposed) {
                dropdownSearch.clearField();
            }
        });
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        if (this.dropdownWidget.group !== null) {
            this.dropdownWidget.group.off();
        }

        ShoppingListQuickSearch.__super__.dispose.call(this);
    }
}));

export default ShoppingListQuickSearch;
