import _ from 'underscore';
import AbstractWidgetView from 'oroui/js/widget/abstract-widget';
import FormStateTrackerView from 'oroform/js/app/views/form-state-tracker-view';

const OrderLineItemDraftCreateWidget = AbstractWidgetView.extend({
    /**
     * @inheritDoc
     */
    options: {
        ...AbstractWidgetView.prototype.options,
        type: 'order-line-item-draft-create',
        actionsContainer: '.widget-actions',
        formFieldsSelector: 'input, select, textarea, button',
        moveAdoptedActions: false
    },

    /**
     * @inheritDoc
     */
    constructor: function OrderLineItemDraftCreateWidget(options) {
        OrderLineItemDraftCreateWidget.__super__.constructor.call(this, options);
    },

    submitHandler(e) {
        if (!e.isDefaultPrevented()) {
            this.options.submitHandler.call(this);
        }
        e.preventDefault();
        e.stopPropagation();

        this.toggleFieldsState(true);
    },

    /**
     * Bind submit handler on own element instead of parent
     * to prevent submit event from bubbling up to the outer page form
     *
     * @private
     */
    _bindSubmitHandler() {
        this.$el.parent().on('submit', this.submitHandler.bind(this));
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        this.widget = this.$el;

        this.listenTo(this, 'widgetReady', this.onWidgetReady);

        OrderLineItemDraftCreateWidget.__super__.initialize.call(this, options);
    },

    onWidgetReady() {
        this.ensureFormStateTracker();
    },

    toggleFieldsState(isDisabled) {
        this.$(this.options.formFieldsSelector)
            .prop('disabled', isDisabled)
            .inputWidget('disable', isDisabled);
    },

    _showLoading() {
        if (this.firstRun || this.saveForm) {
            OrderLineItemDraftCreateWidget.__super__._showLoading.call(this);
        }
    },

    /**
     * @inheritDoc
     */
    _onContentLoad(content) {
        const html = this._getHtml(content);

        if (this.saveForm) {
            this.resetFormStateTracker();
        }

        OrderLineItemDraftCreateWidget.__super__._onContentLoad.call(this, html);

        if (this.saveForm) {
            this.toggleFieldsState(false);
        }

        this.saveForm = false;

        const json = this._getJson(content);
        if (json) {
            this._onJsonContentResponse(json);
        }
    },

    ensureFormStateTracker() {
        const $form = this.$('form');

        if (!$form.length) {
            return;
        }

        const oldTracker = this.subview('formStateTracker');
        const preservedState = oldTracker && !oldTracker.disposed
            ? oldTracker.initialState
            : null;

        if (oldTracker && !oldTracker.disposed) {
            oldTracker.dispose();
        }

        const tracker = new FormStateTrackerView({
            el: $form,
            additionalIgnore: ':input[name$="[drySubmitTrigger]"],:input[name$="[is_price_changed]"]',
            group: 'draftOrder'
        });

        this.subview('formStateTracker', tracker);

        if (preservedState !== null) {
            tracker.initialState = preservedState;
        } else {
            tracker.captureInitialState();
            this.$el.trigger('patchInitialState');
        }
    },

    resetFormStateTracker() {
        const tracker = this.subview('formStateTracker');

        if (tracker && !tracker.disposed) {
            tracker.dispose();
        }
    },

    /**
     * @param {object|string} content
     *
     * @returns {string|null}
     *
     * @private
     */
    _getHtml(content) {
        if (_.isObject(content)) {
            return content.hasOwnProperty('html') ? content.html : null;
        } else {
            return content;
        }
    },

    /**
     * @inheritDoc
     */
    getActionsElement() {
        return null;
    },

    /**
     * @inheritDoc
     */
    show() {
        this.widget
            .addClass('invisible')
            .html(this.$el.children());

        this.setElement(this.widget);

        AbstractWidgetView.prototype.show.call(this);
    }
});
export default OrderLineItemDraftCreateWidget;
