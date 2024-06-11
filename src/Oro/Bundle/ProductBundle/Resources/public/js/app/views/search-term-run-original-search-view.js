import BaseView from 'oroui/js/app/views/base/view';
import DialogWidget from 'oro/dialog-widget';
import $ from 'jquery';
import routing from 'routing';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';

const SearchTermRunOriginalSearchView = BaseView.extend({
    constructor: function SearchTermRunOriginalSearchView(options) {
        SearchTermRunOriginalSearchView.__super__.constructor.call(this, options);
    },

    delegateEvents: function(events) {
        SearchTermRunOriginalSearchView.__super__.delegateEvents.call(this, events);

        this.$el.on(
            'click' + this.eventNamespace(),
            '.search-term-run-original-search-btn',
            this._onRunOriginalSearchClick.bind(this)
        );

        this.$el.on(
            'oro:website-search-term:run-original-search:get-scope-data' + this.eventNamespace(),
            '.search-term-run-original-search-btn',
            this._onGetScopeData.bind(this)
        );

        return this;
    },

    undelegateEvents: function() {
        if (this.$el) {
            this.$el.off(this.eventNamespace());
        }

        SearchTermRunOriginalSearchView.__super__.undelegateEvents.call(this);

        return this;
    },

    _onGetScopeData: function(e, scopeData) {
        const $target = $(e.target);

        scopeData.localization = $target.data('localization');
        scopeData.website = $target.data('website');
        scopeData.customer = $target.data('customer');
        scopeData.customerGroup = $target.data('customerGroup');
    },

    _onRunOriginalSearchClick(e) {
        const $target = $(e.target);
        const scopeData = {};
        $target.trigger('oro:website-search-term:run-original-search:get-scope-data', scopeData);

        _.each(scopeData, (value, key) => {
            scopeData[key] = value;
        });

        const phrase = $target.data('phrase');
        const title = __('oro.websitesearchterm.searchterm.dialog.run_original_search.title', {term: phrase});

        const urlParams = _.extend({}, scopeData, {
            gridName: 'website-search-term-run-original-search-grid',
            search: phrase
        });

        new DialogWidget({
            autoRender: true,
            title,
            url: routing.generate('oro_datagrid_widget', urlParams),
            dialogOptions: {
                modal: true,
                resizable: true,
                autoResize: true
            }
        });
    }
});

export default SearchTermRunOriginalSearchView;
