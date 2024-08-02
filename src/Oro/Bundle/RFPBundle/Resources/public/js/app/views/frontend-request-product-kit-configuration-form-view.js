import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';

const FrontendRequestProductKitConfigurationFormView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'productSelector'
    ]),

    productSelector: '[data-role="kit-item-line-item-product"]',

    /**
     * @inheritdoc
     */
    events() {
        const events = {};

        events[`change ${this.productSelector}`] = this.onChangeProduct;

        return events;
    },

    /**
     * @inheritdoc
     */
    constructor: function FrontendRequestProductKitConfigurationFormView(options) {
        FrontendRequestProductKitConfigurationFormView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        FrontendRequestProductKitConfigurationFormView.__super__.initialize.call(this, options);

        this.$(this.productSelector).each((i, el) => {
            this.toggleQuantityField($(el));
        });
    },

    /**
     * Handler on change
     * @param {Event} e
     */
    onChangeProduct(e) {
        this.toggleQuantityField($(e.target));
    },

    /**
     * Switches the related quantity field to "readonly" mode when minimum quantity equals to maximum quantity.
     * Disables the related quantity field if an empty option is selected.
     *
     * @param {jQuery.Element} $radioField
     */
    toggleQuantityField($radioField) {
        if (!$radioField.is(':checked')) {
            return;
        }

        const $quantityField = $($radioField.data('relatedQuantityField'));
        const hasValue = Boolean($radioField.val());
        const maximumQuantity = $quantityField.data('maximumQuantity');

        let minimumQuantity = $quantityField.data('minimumQuantity');
        if (!minimumQuantity) {
            minimumQuantity = 1;
        }

        if (hasValue) {
            $quantityField.prop('disabled', false);

            if (minimumQuantity === maximumQuantity) {
                // Original value might be smaller than "minimumQuantity" in case of editing product kit.
                // As a result, there is no way to submit valid form, so we have to have value as "minimumQuantity".
                $quantityField.attr('readonly', true).val(minimumQuantity);
            } else {
                $quantityField.prop('readonly', false);
            }

            if (!$quantityField.val()) {
                // Reverts the value of the related quantity field to the state before it was disabled
                // or to the minimum allowed quantity.
                let originalQuantity = $quantityField.data('originalValue');
                if (!originalQuantity) {
                    originalQuantity = minimumQuantity;
                }

                $quantityField.val(originalQuantity);
            }
        } else {
            // Remembers the value of the related quantity before disabling.
            $quantityField.data('originalValue', $quantityField.val());
            $quantityField.val('').attr('disabled', true);
        }
    }
});

export default FrontendRequestProductKitConfigurationFormView;
