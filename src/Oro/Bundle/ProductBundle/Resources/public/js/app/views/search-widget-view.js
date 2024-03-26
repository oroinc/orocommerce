import BaseView from 'oroui/js/app/views/base/view';

const SearchWidgetView = BaseView.extend({
    $cacheCancelButton: null,

    events: {
        'click [type="reset"]': 'onCancel',
        'click .search-widget__reset': 'onClearValue',
        'focusout': 'onFocusout',
        'focusin': 'onFocusin'
    },

    constructor: function SearchWidgetView(...args) {
        SearchWidgetView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        SearchWidgetView.__super__.initialize.call(this, options);

        this.$cacheCancelButton = this.$('[type="reset"]');
        this.$cacheCancelButton.detach();
    },

    onFocusin() {
        this.toggleMode(true);
    },

    onFocusout() {
        if (!!this.$('[name="search"]').val()) {
            return;
        }

        this.toggleMode(false);
    },

    onCancel() {
        this.toggleMode(false);
        this.$('[name="search"]').val('');
    },

    onClearValue() {
        this.$('[name="search"]').val('');
    },

    toggleMode(state) {
        this.$el.toggleClass('search-widget--full', state);

        if (state) {
            this.$cacheCancelButton.appendTo(this.$('[role="search"]'));
        } else {
            this.$cacheCancelButton.detach();
        }
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.$cacheCancelButton.appendTo(this.$('[role="search"]'));
        delete this.$cacheCancelButton;

        SearchWidgetView.__super__.dispose.call(this);
    }
});

export default SearchWidgetView;
