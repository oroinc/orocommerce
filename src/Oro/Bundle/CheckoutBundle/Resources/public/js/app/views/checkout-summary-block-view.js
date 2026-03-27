import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';

const CheckoutSummaryBlockView = BaseView.extend({
    constructor: function CheckoutSummaryBlockView(...args) {
        CheckoutSummaryBlockView.__super__.constructor.apply(this, args);
    },

    delegateEvents(...args) {
        CheckoutSummaryBlockView.__super__.delegateEvents.apply(this, args);

        $(document).on(
            `shown.bs.dropdown${this.eventNamespace()} clearMenus${this.eventNamespace()}`,
            this.collapseSummary.bind(this)
        );
    },

    undelegateEvents() {
        $(document).off(this.eventNamespace());

        CheckoutSummaryBlockView.__super__.undelegateEvents.call(this);
    },

    collapseSummary() {
        this.$el.collapse('hide');
    }
});

export default CheckoutSummaryBlockView;
