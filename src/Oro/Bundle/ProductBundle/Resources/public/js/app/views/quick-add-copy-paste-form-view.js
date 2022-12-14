import _ from 'underscore';
import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';
import formToAjaxOptions from 'oroui/js/tools/form-to-ajax-options';

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

    onSubmit(e) {
        e.preventDefault();

        if (!this.validator.element(this.$field)) {
            return false;
        }


        this.submitForm({
            beforeSend: () => {
                this.disableForm();
            }
        }).always(() => this.enableForm());
    },

    submitForm(options) {
        const ajaxOptions = formToAjaxOptions(this.$el, {
            ...options,
            success: response => {
                if (response.messages) {
                    Object.entries(response.messages).forEach(([type, messages]) => {
                        messages.forEach(message => mediator.execute('showMessage', type, message));
                    });
                }
                if (response.collection) {
                    const {errors = [], items} = response.collection;
                    errors.forEach(error => mediator.execute('showMessage', 'error', error.message));

                    if (items && items.length && !this.disposed) {
                        const _items = items.map(item => {
                            // omit index attr, since it is not an index of a model in collection
                            const {index, ...attrs} = item;
                            return attrs;
                        });
                        this.productsCollection.addQuickAddRows(_items, {ignoreIncorrectUnit: false});
                    }
                }
            }
        });

        return $.ajax(ajaxOptions);
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

    isEmptyField() {
        return this.$field.val().length === 0;
    }
});

export default QuickAddCopyPasteFormView;
