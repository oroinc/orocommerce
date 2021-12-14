import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import routing from 'routing';
import template from 'tpl-loader!oroproduct/templates/search-autocomplete.html';
import 'jquery-ui/tabbable';

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
        focus: '_onInputRefresh',
        keydown: '_onKeyDown'
    },

    previousValue: '',

    autocompleteItems: '[role="option"]',

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
        this.comboboxId = `combobox-${this.cid}`;
        this.$el.attr({
            'role': 'combobox',
            'autocomplete': 'off',
            'aria-haspopup': true,
            'aria-expanded': false,
            'aria-autocomplete': 'list',
            'aria-controls': this.comboboxId
        });

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
            inputString: this.getInputString(),
            comboboxId: this.comboboxId
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
        this.$el.attr('aria-expanded', null);
        $('body').off(this.eventNamespace());

        SearchAutocompleteView.__super__.dispose.call(this);
    },

    close() {
        if (!this.$popup) {
            return;
        }

        this.$popup.remove();
        this.$popup = null;
        this.$el.attr({
            'aria-expanded': false,
            'aria-activedescendant': null
        });
        this.undoFocusStyle();
    },

    hideCombobox() {
        if (!this.$popup) {
            return;
        }

        this.$el.attr({
            'aria-expanded': false,
            'aria-activedescendant': null
        });
        this.$popup.hide();
        this.gerSelectedOption().removeAttr('aria-selected');
        this.undoFocusStyle();
    },

    showCombobox() {
        if (!this.$popup) {
            return;
        }

        this.$popup.show();
    },

    hasSelectedOption() {
        return this.gerSelectedOption().length > 0;
    },

    getAutocompleteItems() {
        return this.$el.next().find(this.autocompleteItems);
    },

    gerSelectedOption() {
        return this.getAutocompleteItems().filter((i, el) => $(el).attr('aria-selected') === 'true');
    },

    getNextOption() {
        const $options = this.getAutocompleteItems();
        const $activeOption = this.gerSelectedOption();

        if (
            $activeOption.length === 0 ||
            ($options.length - 1 === $options.index($activeOption))
        ) {
            return $options.first();
        }

        return $options.eq($options.index($activeOption) + 1);
    },

    getPreviousOption() {
        const $options = this.getAutocompleteItems();
        const $activeOption = this.gerSelectedOption();

        if (
            $activeOption.length === 0 ||
            $options.index($activeOption) === 0
        ) {
            return $options.last();
        }

        return $options.eq($options.index($activeOption) - 1);
    },

    /**
     * @param {string} direction
     */
    goToOption(direction = 'down') {
        const $options = this.getAutocompleteItems();
        const $activeOption = direction === 'down'
            ? this.getNextOption()
            : this.getPreviousOption()
        ;

        this.showCombobox();
        $options.attr('aria-selected', false);
        $activeOption.attr('aria-selected', true);
        this.$el.attr('aria-activedescendant', $activeOption.attr('id'));
    },

    executeSelectedOption() {
        if (this.hasSelectedOption()) {
            this.gerSelectedOption().find(':first-child')[0].click();
        }
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
            this.$el.attr('aria-expanded', true);

            this.getAutocompleteItems().each((i, el) => {
                $(el).attr({
                    'id': _.uniqueId('item-'),
                    'aria-selected': false
                }).find(':tabbable').attr('tabindex', -1);
            });
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

    _onKeyDown(event) {
        switch (event.key) {
            case 'Tab':
            case 'Escape':
            case 'Esc': // key name in IE11
                this.hideCombobox();
                break;
            case 'ArrowUp':
            case 'Up': // key name in IE11
                event.preventDefault();
                this.goToOption('up');
                break;
            case 'ArrowDown':
            case 'Down': // key name in IE11
                event.preventDefault();
                this.goToOption('down');
                break;
            case 'Enter':
            case ' ':
                this.executeSelectedOption();
                break;
            default:
                break;
        }

        this.undoFocusStyle();
    },

    undoFocusStyle() {
        this.$el.toggleClass('undo-focus', this.hasSelectedOption());
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
        }

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
