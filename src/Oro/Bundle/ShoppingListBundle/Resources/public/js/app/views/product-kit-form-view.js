import {throttle} from 'underscore';
import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import numberFormatter from 'orolocale/js/formatter/number';

const ProductKitFormView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'kitLineItemProductSelector', 'kitLineItemQuantitySelector', 'subtotalUrl', 'maskClass'
    ]),

    kitLineItemProductSelector: '[data-role="kit-line-item-product"]',

    kitLineItemQuantitySelector: '[data-role="kit-line-item-quantity"]',

    subtotalUrl: void 0,

    maskClass: 'loading-blur',

    /**
     * @inheritdoc
     */
    events() {
        const events = {};

        events[`change ${this.kitLineItemProductSelector}`] = this.onProductChange;
        events[`change ${this.kitLineItemQuantitySelector}`] = this.getSubtotal;

        return events;
    },

    /**
     * @inheritdoc
     */
    constructor: function ProductKitFormView(options) {
        this.getSubtotal = throttle(this.getSubtotal.bind(this), 20);
        ProductKitFormView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        if (this.subtotalUrl === void 0) {
            throw new Error('Option "subtotalUrl" is required for ProductKitFormView');
        }

        ProductKitFormView.__super__.initialize.call(this, options);

        this.$(this.kitLineItemProductSelector).each((i, el) => {
            this.lockRelatedElements($(el));
        });
    },

    /**
     * @inheritdoc
     */
    delegateEvents(events) {
        ProductKitFormView.__super__.delegateEvents.call(this, events);
        // Handler is moved to parent element to allow preventing submit by validator
        this.$el.parent().on(`submit${this.eventNamespace()}`, this.onSubmit.bind(this));
        // Elements are rendered outside but belong to form
        $(`[data-form-element="${this.$el.attr('id')}"]`)
            .on(`change${this.eventNamespace()}`, this.getSubtotal.bind(this));
    },

    /**
     * @inheritdoc
     */
    undelegateEvents() {
        if (this.$el) {
            // this.$el might be not set yet
            this.$el.parent().off(this.eventNamespace());
            $(`[data-form-element="${this.$el.attr('id')}"]`).off(this.eventNamespace());
        }
        ProductKitFormView.__super__.undelegateEvents.call(this);
    },

    /**
     * Handler on submit form
     * @param e
     */
    onSubmit(e) {
        e.preventDefault();
    },

    /**
     * Validates the form with extra fields that outside of it, returns true if it is valid, false otherwise
     * @returns {boolean}
     */
    validateForm() {
        const isValid = this.$el.validate().form();
        const extraIsValid = $(this.$el.data('extra-form-selector')).validate().form();
        return isValid && extraIsValid;
    },

    /**
     * Gets actual total price
     * @param {Event} e
     */
    getSubtotal(e) {
        if (!this.validateForm()) {
            return;
        }

        const data = this.$el.add(this.$el.data('extra-form-selector')).serializeArray();

        if (!this._activeAjaxActions) {
            this._activeAjaxActions = 0;
        }

        $.ajax({
            type: 'POST',
            url: this.subtotalUrl,
            beforeSend: () => {
                this._activeAjaxActions++;
                $(`#${this.$el.attr('id')}totals`).addClass(this.maskClass);
            },
            data: data,
            success: response => {
                if (this.disposed) {
                    return;
                }

                const {subtotal} = response;

                if (subtotal) {
                    $(`#${this.$el.attr('id')}amount`).text(
                        numberFormatter.formatCurrency(subtotal.amount, subtotal.currency)
                    );
                }
            },
            complete: () => {
                if (this.disposed) {
                    return;
                }
                this._activeAjaxActions--;
                if (this._activeAjaxActions === 0) {
                    $(`#${this.$el.attr('id')}totals`).removeClass(this.maskClass);
                }
            }
        });
    },

    /**
     * Handler on change
     * @param {Event} e
     */
    onProductChange(e) {
        this.lockRelatedElements($(e.target));
        this.getSubtotal(e);
    },

    /**
     * Makes related elements to be in "readonly" mode.
     * Enables "readonly" mode when minimum quantity equals to maximum quantity.
     * @param {jQuery.Element} $el
     */
    lockRelatedElements($el) {
        const $relatedElements = $($el.data('relatedElements'));
        const hasValue = Boolean($el.val());

        $relatedElements.each((i, relatedEl) => {
            const $relatedEl = $(relatedEl);
            const maximumQuantity = $relatedEl.data('maximumQuantity');

            let minimumQuantity = $relatedEl.data('minimumQuantity') || 1;
            if (!minimumQuantity) {
                minimumQuantity = 1;
            }

            if (hasValue) {
                if (minimumQuantity === maximumQuantity) {
                    // Original value might be smaller than "minimumQuantity" in case of editing product kit.
                    // As a result, there is no way to submit valid form, so we have to have value as "minimumQuantity".
                    $relatedEl.attr('readonly', true).val(minimumQuantity);
                } else {
                    $relatedEl.prop('readonly', false);
                }

                if (!$relatedEl.val()) {
                    let relatedElValue = $relatedEl.data('value') || 1;
                    if (!relatedElValue) {
                        relatedElValue = 1;
                    }

                    $relatedEl.val(relatedElValue);
                }
            } else {
                // Update attributes only for selected elements,
                // otherwise the last empty radio button will overwrite "minimumQuantity"
                if ($el.is(':checked')) {
                    $relatedEl.val('').attr('readonly', true);
                }
            }
        });
    }
});

export default ProductKitFormView;
