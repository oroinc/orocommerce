import $ from 'jquery';
import {debounce} from 'underscore';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import routing from 'routing';
import messenger from 'oroui/js/messenger';
import BaseView from 'oroui/js/app/views/base/view';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';

const OrderLineItemDraftDiscountsTaxesView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'listenFieldsIds',
        'updateRouteName',
        'updateRouteParams',
        'refreshOnOpen'
    ]),

    updateRouteName: '',

    refreshOnOpen: false,

    currentRequest: null,

    UPDATE_DELAY: 300,

    events: {
        'shown.bs.collapse': 'onCollapseShow'
    },

    constructor: function OrderLineItemDraftDiscountsTaxesView(...args) {
        this.updateRouteParams = {};
        this.listenFieldsIds = [];
        this.debounceOnFieldChange = () => {
            mediator.execute('isRequestPending', true);
            (debounce(this.onFieldChange, this.UPDATE_DELAY).bind(this))();
        };
        OrderLineItemDraftDiscountsTaxesView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.$formEl = this.$el.closest('form');

        OrderLineItemDraftDiscountsTaxesView.__super__.initialize.call(this, options);

        this.subview('loadingMask', new LoadingMaskView({container: this.$el}));

        const fieldsSelector = this.listenFieldsIds.map(id => `#${id}`).join(',');

        this.$formEl.on(
            `input${this.eventNamespace()} change${this.eventNamespace()}`,
            fieldsSelector, this.debounceOnFieldChange.bind(this)
        );
    },

    undelegateEvents() {
        if (this.$formEl) {
            this.$formEl.off(this.eventNamespace());
        }

        OrderLineItemDraftDiscountsTaxesView.__super__.undelegateEvents.call(this);

        return this;
    },

    updateContent() {
        if (this.currentRequest) {
            this.currentRequest.abort();
        }

        this.subview('loadingMask').show();

        this.currentRequest = $.ajax({
            url: routing.generate(this.updateRouteName, this.updateRouteParams),
            type: 'POST',
            data: this.$formEl.serialize()
        });

        this.currentRequest
            .done(response => {
                if (response.success) {
                    this.$el.find('[data-html-response]').each((index, el) => {
                        const $el = $(el);
                        const key = $el.data('htmlResponse');

                        if (key in response) {
                            $el.html(response[key]);
                        }
                    });
                }
            })
            .fail((jqXHR, textStatus) => {
                if (textStatus !== 'abort') {
                    this.$el.find('[data-html-response]').each((index, el) => $(el).html(''));
                    messenger.notificationMessage(
                        'error',
                        __('oro.order.orderlineitem.discounts_taxes.update_error'),
                        {
                            container: this.$el,
                            dismissible: false
                        }
                    );
                }
            })
            .always(() => {
                this.subview('loadingMask').hide();
                this.currentRequest = null;
            });
    },

    onFieldChange() {
        this.updateContent();
    },

    onCollapseShow() {
        if (this.refreshOnOpen) {
            this.updateContent();
        }
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        if (this.currentRequest) {
            this.currentRequest.abort();
            this.currentRequest = null;
        }

        clearTimeout(this.updateOutTimer);

        if (this.$formEl) {
            this.$formEl.off(this.eventNamespace());
        }

        delete this.updateRouteParams;
        delete this.listenFieldsIds;

        OrderLineItemDraftDiscountsTaxesView.__super__.dispose.call(this);
    }
});

export default OrderLineItemDraftDiscountsTaxesView;
