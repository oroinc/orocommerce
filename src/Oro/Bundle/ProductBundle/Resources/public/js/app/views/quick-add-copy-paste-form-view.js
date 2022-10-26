import _ from 'underscore';
import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import QuantityHelper from 'oroproduct/js/app/quantity-helper';
import BaseView from 'oroui/js/app/views/base/view';
import formToAjaxOptions from 'oroui/js/tools/form-to-ajax-options';
import messenger from 'oroui/js/messenger';

const QuickAddCopyPasteFormView = BaseView.extend({
    /**
     * @property
     */
    field: 'textarea',

    /**
     * @property {jQuery.validator}
     */
    validator: null,

    /**
     * @property {boolean} - can block interface from user interaction during form submit processing
     */
    disabled: false,

    events: {
        'keyup textarea': 'onFieldChange',
        'focusout textarea': 'onFieldChange',
        'submit': 'onSubmit'
    },

    constructor: function QuickAddCopyPasteFormView(options) {
        // Use debounce to give some time for jquery.validate to check the value
        this.onFieldChange = _.debounce(this.onFieldChange.bind(this), 50);

        QuickAddCopyPasteFormView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        if (!options.productsCollection) {
            throw new Error('Option `productsCollection` is require for QuickAddCopyPasteFormComponent');
        }

        this.productsCollection = options.productsCollection;

        QuickAddCopyPasteFormView.__super__.initialize.call(this, options);

        this.$field = this.$(this.field);

        // Run element validation to make it validated immediately on input (i.e. don't wait for form submit)
        this.validator = this.$el.validate();
        this.validator.element(this.$field);

        // Init parser regexp
        const regexParts = this.$field.data('item-parse-pattern').match(/^\/(.*?)\/(g?i?m?y?)$/);
        if (regexParts === null || regexParts.length < 2) {
            throw new Error('The field must must have a data attribute with valid RegExp string');
        }
        this.itemParseRegExp = new RegExp(regexParts[1], regexParts[2]);
    },

    dispose() {
        if (!this.disposed) {
            return;
        }

        delete this.$field;
        delete this.validator;

        QuickAddCopyPasteFormView.__super__.dispose.call(this);
    },

    onFieldChange() {
        this._toggleSubmitButton(this.disabled || this.$field.hasClass('error'));
    },

    /**
     * @param {boolean} disable
     * @private
     */
    _toggleSubmitButton: function(disable) {
        const disabled = disable || this.isEmptyField();
        this.$('button:submit').attr('disabled', disabled);
    },

    async onSubmit(e) {
        e.preventDefault();

        if (!this.validator.element(this.$field)) {
            return false;
        }

        const ajaxOptions = formToAjaxOptions(this.$el, {
            success: response => {
                if (response.collection) {
                    _.each(response.collection.errors, error => {
                        messenger.notificationMessage('error', error.message);
                    });

                    if (response.collection.items && response.collection.items.length) {
                        mediator.trigger('quick-add-import-form:submit', response.collection.items);
                    }
                }

                if (response.messages) {
                    _.each(response.messages, (messages, type) => {
                        _.each(messages, message => {
                            messenger.notificationMessage(type, message);
                        });
                    });
                }
            },
            complete: xhr => {
                this.enableForm();
            }
        });

        this.disableForm();

        this.trigger('beforeContentLoad', this);
        this.loading = $.ajax(ajaxOptions);
    },

    /**
     * Blocks form from user interaction
     */
    disableForm() {
        this.disabled = true;
        this.$field.attr('disabled', true);
        this._toggleSubmitButton(true);
    },

    /**
     * Enable form to user interaction
     */
    enableForm() {
        this.disabled = false;
        this.$field.removeAttr('disabled');
        this._toggleSubmitButton(false);
    },

    /**
     * Parses text in field, creates an array of items, and merges items that have the same sku and unit
     *
     * @param {Array<string>} lines
     * @return {[{raw: [string], sku: string, quantity: string, unit_label: string|null}]}
     * @private
     */
    _prepareFieldItems(lines) {
        const items = [];

        lines.forEach(line => {
            const [raw, sku, quantity, unitLabel = ''] = line.match(this.itemParseRegExp);

            if (!sku || !quantity) {
                // row must match the pattern and contains SKU and quantity
                return;
            }

            const product = {
                raw: [raw],
                sku: sku.toUpperCase(),
                quantity: QuantityHelper.getQuantityNumberOrDefaultValue(quantity, NaN),
                unit_label: unitLabel.toUpperCase() || null
            };

            const existItem = items
                .find(item => item.sku === product.sku && item.unit_label === product.unit_label);

            if (existItem) {
                existItem.raw = existItem.raw.concat(product.raw);
                existItem.quantity += product.quantity;
            } else {
                items.push(product);
            }
        });

        return items;
    },

    _showErrorMessage() {
        const fieldName = this.$field.attr('name');
        if (!this.isEmptyField()) {
            this.validator.showBackendErrors({
                [fieldName]: {
                    errors: [__('oro.product.frontend.quick_add.copy_paste.error')]
                }
            });
        }
    },

    isEmptyField() {
        return this.$field.val().length === 0;
    }
});

export default QuickAddCopyPasteFormView;
