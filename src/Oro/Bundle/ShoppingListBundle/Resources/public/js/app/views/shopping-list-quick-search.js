import {extend, debounce} from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import HighlightTextView from 'oroui/js/app/views/highlight-text-view';

const ESC_KEY_CODE = 27;

const ShoppingListQuickSearch = BaseView.extend(extend({}, ElementsHelper, {
    optionNames: BaseView.prototype.optionNames.concat([
        'minimumResultsForSearch'
    ]),

    events: {
        'input [data-role="quick-search"]': 'onSearch',
        'keydown [data-role="quick-search"]': 'preventClose',
        'click .clear-search-button': 'onClick'
    },

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

        this.onSearch = debounce(this.onSearch, 200);
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
        if (!this.dropdownWidget.dropdown) {
            return;
        }

        this.$searchField = this.$el.find('[data-role="quick-search"]');

        this.clearField(true);

        this.showSearch();

        this.subview('highlight', new HighlightTextView({
            el: this.dropdownWidget.dropdown,
            notFoundClass: 'hide',
            alwaysDisplaySelector: '.dropdown-search',
            highlightSelectors: [
                'a.dropdown-item'
            ],
            toggleSelectors: {
                'li[role="menuitem"]': '.items-group',
                '.items-group': '.item-container'
            }
        }));


        this.dropdownWidget.group.on('show.bs.dropdown', e => {
            this.clearField();
        });
    },

    /**
     * Remove text for input
     * @param silent
     */
    clearField(silent = false) {
        this.$searchField.val('');

        if (!silent) {
            this.$searchField
                .trigger('input')
                .trigger('change');
        }
    },

    /**
     * Toggle search field visibility
     */
    showSearch() {
        this.$el.toggle(
            this.dropdownWidget.dropdown
                .find('.items-group > li[role="menuitem"]').length > this.minimumResultsForSearch
        );
    },

    /**
     * Click on button handler
     * @param e
     */
    onClick(e) {
        e.preventDefault();
        e.stopPropagation();
        this.clearField();
        this.$searchField.focus();
    },

    /**
     * Prevent closing dropdown if button ESC was pressed
     * @param e
     */
    preventClose(e) {
        if (e.keyCode === ESC_KEY_CODE) {
            e.stopPropagation();

            this.clearField();
        }
    },
    /**
     * On input text handler
     * @param e
     */
    onSearch(e) {
        const minHeight = this.dropdownWidget.dropdown.find('.item-container').height();

        this.dropdownWidget.dropdown.find('.item-container').css({
            minHeight
        });

        this.$el.find('.clear-search-button').attr('disabled', e.target.value.length === 0);

        this.subview('highlight').update(e.target.value);

        if (e.target.value.length > 0 &&
            !this.subview('highlight').isElementHighlighted(this.dropdownWidget.dropdown)
        ) {
            this.dropdownWidget.dropdown.find('.items-group').addClass('hide');
            if (!this.$el.find('.no-matches').length) {
                this.$el.append(
                    `<span class="no-matches" role="alert">
                        ${__('oro.frontend.shoppinglist.dropdown.quick_search.no_match')}
                    </span>`
                );
            }
        } else {
            this.$el.find('[role="alert"]').remove();
        }
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
    }
}));

export default ShoppingListQuickSearch;
