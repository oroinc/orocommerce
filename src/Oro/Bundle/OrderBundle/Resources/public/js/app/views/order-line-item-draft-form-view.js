import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import LineItemProductView from 'oroproduct/js/app/views/line-item-product-view';
import QuantityHelper from 'oroproduct/js/app/quantity-helper';

const OrderLineItemDraftFormView = LineItemProductView.extend({
    elements: {
        ...LineItemProductView.prototype.elements,
        id: '[name$="[product]"]:first',
        product: '[name$="[product]"]:first',
        quantity: '[name$="[quantity]"]:first',
        unit: '[name$="[productUnit]"]:first',
        isPriceChanged: '[name$="[price][is_price_changed]"]:first',
        priceValue: '[name$="[price][value]"]:first',
        kitItemLineItems: '.order-line-item-kit-item-line-items',
        drySubmitTrigger: '[name$="[drySubmitTrigger]"]',
        isFreeForm: '[name$="[isFreeForm]"]'
    },

    modelElements: {
        ...LineItemProductView.prototype.modelElements,
        drySubmitTrigger: 'drySubmitTrigger',
        isFreeForm: 'isFreeForm'
    },

    optionNames: LineItemProductView.prototype.optionNames.concat([
        'freeFormUnits',
        'tierPrices',
        'formFieldsSelector'
    ]),

    formFieldsSelector: '[data-draft-update-field], .error > select',

    events: {
        'click [data-role="select-order-line-item-draft"]': 'switchToFreeForm',
        'click [data-line-items-presentation]': 'onClickLineItemsPresentation'
    },

    listen: {
        'pricing:product-price:lock mediator': 'onLineItemProductPriceLock',
        'pricing:product-price:unlock mediator': 'onLineItemProductPriceUnlock'
    },

    UPDATE_DELAY: 250,

    constructor: function OrderLineItemDraftFormView(options) {
        this.drySubmit = this.drySubmit.bind(this);
        OrderLineItemDraftFormView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        this.$form = this.$('form');

        OrderLineItemDraftFormView.__super__.initialize.call(this, options);
    },

    handleLayoutInit() {
        OrderLineItemDraftFormView.__super__.handleLayoutInit.call(this);

        const tierPrices = {};

        // Collect tier prices from line item views to pass them to the refresh tier prices on current line item
        mediator.trigger('pricing:collect:tier-prices', tierPrices);

        if (this.tierPrices) {
            Object.assign(tierPrices, this.tierPrices);
        }

        mediator.trigger('pricing:refresh:products-tier-prices', tierPrices);

        this.lastChangedFieldName = null;

        this.$el.on(`change${this.eventNamespace()}`, this.formFieldsSelector, this.onFieldChange.bind(this));
        this.$el.on(`focusin${this.eventNamespace()}`, this.formFieldsSelector, this.onFieldFocusIn.bind(this));
        this.$el.on(`focusout${this.eventNamespace()}`, this.formFieldsSelector, this.onFieldFocusOut.bind(this));

        this.handleDisableFieldsOnProductChange();
    },

    getDelayByElement(element) {
        const delay = this.$(element).data('delay');

        if (delay === void 0) {
            return this.UPDATE_DELAY;
        }

        return delay;
    },

    onFieldChange(event) {
        this.lastChangedFieldName = event.target.name;

        if (this.$(event.target).inputWidget() || event.manually === false) {
            this.delayDrySubmit(this.getDelayByElement(event.target));
        }
    },

    onFieldFocusIn() {
        clearTimeout(this.updateOutTimer);
        mediator.execute('isRequestPending', false);
    },

    onFieldFocusOut(event) {
        if (!this.lastChangedFieldName) {
            return;
        }

        this.delayDrySubmit(this.getDelayByElement(event.target));
    },

    delayDrySubmit(delay) {
        if (delay > 0) {
            mediator.execute('isRequestPending', true);
            this.updateOutTimer = setTimeout(this.drySubmit, delay);
        } else {
            this.drySubmit();
        }
    },

    drySubmit() {
        if (!this.lastChangedFieldName) {
            return;
        }

        const fieldName = this.lastChangedFieldName;
        this.lastChangedFieldName = null;

        this.getElement('drySubmitTrigger').val(this.getDrySubmitTriggerName(fieldName));

        this.$form.trigger('submit', {isDrySubmit: true});
    },

    onProductChanged() {
        OrderLineItemDraftFormView.__super__.onProductChanged.call(this);

        this.handleDisableFieldsOnProductChange();
    },

    handleDisableFieldsOnProductChange() {
        const modelProductId = this.model.get('id');

        if (
            (modelProductId && this.model.hasChanged('id')) ||
            (!modelProductId && this.model.get('isFreeForm') !== 1)
        ) {
            this.disableProductAttributeFields();

            if (!modelProductId) {
                this.$('.product-image').remove();
            }
        }
    },

    disableProductAttributeFields() {
        this.getElement('quantity').prop('disabled', true);
        this.getElement('unit').prop('disabled', true).inputWidget('disable', true);
        this.getElement('priceValue').prop('disabled', true);
    },

    switchToFreeForm(event) {
        const value = $(event.target).data('draftFreeForm');
        this.getElement('isFreeForm').val(value === 0 ? 1 : 0).trigger('change');
        this.disableProductAttributeFields();

        this.drySubmit();
    },

    /**
     * @param {String} inputName
     * @returns {String}
     */
    getDrySubmitTriggerName(inputName) {
        return inputName.replace(this.$form.attr('name'), '');
    },

    /**
     * Keep empty quantity as empty string instead of converting to NaN
     * @inheritDoc
     */
    viewToModelElementValueTransform(elementViewValue) {
        return QuantityHelper.getQuantityNumberOrDefaultValue(elementViewValue, elementViewValue);
    },

    onClickLineItemsPresentation(event) {
        event.preventDefault();

        const ids = $(event.currentTarget).data('line-items-presentation').toString().split(',').map(id => id.trim());

        mediator.trigger(`line-items-datagrid-presentation:order-line-items-edit-grid:apply`, {
            orderLineItemId: {
                type: '9',
                value: ids.join(',')
            }
        });

        $(event.currentTarget).closest('[data-role="created-entity-item-presentation"]').remove();
    },

    onLineItemProductPriceLock($priceValueEl) {
        if ($priceValueEl.is(this.getElement('priceValue'))) {
            this.getElement('isPriceChanged').val(1);
        }
    },

    onLineItemProductPriceUnlock($priceValueEl) {
        if ($priceValueEl.is(this.getElement('priceValue'))) {
            this.getElement('isPriceChanged').val(0);
        }
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        clearTimeout(this.updateOutTimer);
        mediator.execute('isRequestPending', false);

        OrderLineItemDraftFormView.__super__.dispose.call(this);
    }
});

export default OrderLineItemDraftFormView;
