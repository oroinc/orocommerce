import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import routing from 'routing';
import template from 'tpl-loader!oroproduct/templates/search-autocomplete.html';

const SearchAutocompleteView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'template', 'delay', 'charLimit', 'resultsLimit', 'resultsNumber', 'autocompleteRoute'
    ]),

    /**
     * - delay before autocomplete request should run
     * @property {number} delay
     */
    delay: 200,

    /**
     * - minimum chars number to run autocomplete request
     * @property {number} charLimit
     */
    charLimit: 2,

    /**
     * - underscore template for autocomplete popup
     * @property {function} template
     */
    template: template,

    /**
     * - autocomplete symfony route
     * @property {string} autocompleteRoute
     */
    autocompleteRoute: 'oro_product_frontend_product_search_autocomplete',

    $popup: null,

    searchXHR: null,

    events: {
        change: '_onInputChange',
        keyup: '_onInputChange',
        focus: '_onInputRefresh'
    },

    previousValue: '',

    /**
     * @inheritdoc
     */
    constructor: function SearchAutocompleteView(options) {
        SearchAutocompleteView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.$el.attr('autocomplete', 'off');

        this.renderSuggestions = _.debounce(this.renderSuggestions.bind(this), this.delay);

        $('body').on(`click${this.eventNamespace()}`, this._onOutsideAction.bind(this));

        SearchAutocompleteView.__super__.initialize.call(this, options);
    },

    getInputString() {
        return this.$el.val();
    },

    /**
     * @inheritdoc
     */
    getTemplateData: function(data = {}) {
        return Object.assign(data, {
            inputString: this.getInputString()
        });
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }
        this.close();

        $('body').off(this.eventNamespace());

        SearchAutocompleteView.__super__.dispose.call(this);
    },

    close() {
        if (!this.$popup) {
            return;
        }

        this.$popup.remove();
        this.$popup = null;
    },

    _getSearchXHR(inputString) {
        const autocompleteUrl = routing.generate(this.autocompleteRoute, {
            search: inputString
        });

        return Backbone.ajax({
            dataType: 'json',
            url: autocompleteUrl
        });
    },

    /**
     * @inheritdoc
     */
    render(suggestions) {
        this.close();

        if (this.getInputString().length) {
            this.$popup = $(this.template(this.getTemplateData(suggestions)));
            this.$el.after(this.$popup);
        }

        return this;
    },

    _shouldShowPopup(inputString) {
        return inputString && inputString.length >= this.charLimit;
    },

    renderSuggestions(inputString) {
        /**
         * Prevent request race condition
         */
        if (this.searchXHR) {
            this.searchXHR.abort();
        }

        this.searchXHR = this._getSearchXHR(inputString);
        this.searchXHR
            .then(this.render.bind(this))
            .always(() => {
                delete this.searchXHR;
            })
        ;
    },

    _onInputChange(event) {
        const inputString = this.getInputString();
        if (inputString === this.previousValue) {
            return;
        }

        this._shouldShowPopup(inputString)
            ? this.renderSuggestions(inputString)
            : this.close();

        this.previousValue = inputString;
    },

    _onInputRefresh(event) {
        const inputString = this.getInputString();

        if (!inputString.length && this.searchXHR) {
            this.searchXHR.abort();
        };

        this._shouldShowPopup(inputString)
            ? this.renderSuggestions(inputString)
            : this.close();
    },

    _onOutsideAction(event) {
        if (!((event.target === this.el) || (this.$popup && $.contains(this.$popup[0], event.target)))) {
            this.close();
        }
    }
});

export default SearchAutocompleteView;
