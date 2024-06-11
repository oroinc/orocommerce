import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';
import _ from 'underscore';

const SearchTermRunOriginalSearchFormView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['delimiter', 'itemTemplate']),

    itemTemplate: _.template('<a href="#" class="dropdown-item search-term-run-original-search-btn ellipsis"></a>'),

    delimiter: '�',

    $form: null,

    constructor: function SearchTermRunOriginalSearchFormView(options) {
        SearchTermRunOriginalSearchFormView.__super__.constructor.call(this, options);
    },

    preinitialize() {
        this.$form = $('form[name="search_term"]');
    },

    initialize(options) {
        SearchTermRunOriginalSearchFormView.__super__.initialize.call(this, options);
        this.setOriginalSearchBtnContent();
    },

    delegateEvents(events) {
        SearchTermRunOriginalSearchFormView.__super__.delegateEvents.call(this, events);

        if (this.$form.length) {
            this.$form.on(
                'change' + this.eventNamespace(),
                '[name="search_term[phrases]"]',
                this._onPhrasesChange.bind(this)
            );

            this.$form.on(
                'oro:website-search-term:run-original-search:get-scope-data' + this.eventNamespace(),
                '.search-term-run-original-search-btn',
                this._onGetScopeData.bind(this)
            );
        }

        return this;
    },

    undelegateEvents() {
        if (this.$form.length) {
            this.$form.off(this.eventNamespace());
        }

        SearchTermRunOriginalSearchFormView.__super__.undelegateEvents.call(this);

        return this;
    },

    _onGetScopeData(e, scopeData) {
        e.stopPropagation();

        const $target = $(e.target);
        const $collectionItem = $target.closest('.oro-collection-item');
        const scopeSelectorPrefix = '[name^="search_term[scopes]["][name$="]';

        scopeData.localization = $collectionItem.find(scopeSelectorPrefix + '[localization]"]').val();
        scopeData.website = $collectionItem.find(scopeSelectorPrefix + '[website]"]').val();
        scopeData.customerGroup = $collectionItem.find(scopeSelectorPrefix + '[customerGroup]"]').val();
        scopeData.customer = $collectionItem.find(scopeSelectorPrefix + '[customer]"]').val();
    },

    setOriginalSearchBtnContent() {
        const phrasesFieldValue = this.$form.find('[name="search_term[phrases]"]').val().trim();
        let phrases = [];
        if (phrasesFieldValue.length > 1) {
            phrases = phrasesFieldValue.split(this.delimiter);
        }

        this.$form.find('.search-term-run-original-search-dropdown').each((index, searchDropdown) => {
            const $searchDropdown = $(searchDropdown);
            const $dropdownMenu = $searchDropdown.find('.dropdown-menu');

            if (phrases.length === 0) {
                $searchDropdown.addClass('hide');
                return;
            }

            $searchDropdown.removeClass('hide');

            $dropdownMenu
                .find('a.dropdown-item')
                .remove();

            phrases.forEach(phrase => {
                const $item = $(this.itemTemplate());

                $item.data('phrase', phrase);
                $item.text(phrase);
                $item.attr('title', phrase);

                $dropdownMenu.append($item);
            });
        });
    },

    _onPhrasesChange() {
        this.setOriginalSearchBtnContent();
    }
});

export default SearchTermRunOriginalSearchFormView;
