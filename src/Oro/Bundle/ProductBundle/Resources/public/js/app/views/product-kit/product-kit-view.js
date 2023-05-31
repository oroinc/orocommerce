import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';
import datetimeFormatter from 'orolocale/js/formatter/datetime';

const ProductKitView = BaseView.extend({
    autoRender: true,

    optionNames: BaseView.prototype.optionNames.concat([
        'fieldsNamespace', 'fields', 'kitItemId', 'sortOrderFieldSelector',
         'submitted'
    ]),

    fields: null,

    kitItemId: null,

    sortOrderFieldSelector: null,

    events: {
        'change input[name], select': 'onChangeInputs',
        'show.bs.collapse': 'onShow',
        'hide.bs.collapse': 'onHide'
    },

    constructor: function ProductKitView(...args) {
        ProductKitView.__super__.constructor.apply(this, args);
    },

    render() {
        ProductKitView.__super__.render.call(this);

        this.updateFields();

        if (!this.$el.data('rendered') && !this.kitItemId && !this.submitted) {
            this.$(this.sortOrderFieldSelector).val(this.getMaxSortOrder() + 1);
        }

        this.$el.attr('data-rendered', true);
    },

    /**
     * Get max sort order value from product kit collection
     * @returns {number}
     */
    getMaxSortOrder() {
        return Math.max(
            0,
            ...this.$el.closest('[data-role="collection-container"]')
                .find(this.sortOrderFieldSelector) // Find all sibling sortOrder fields
                .not(this.$(this.sortOrderFieldSelector)) // Ignore own sortOrder field
                .toArray().map(field => field.value ? parseFloat(field.value) : 0)
        );
    },

    validate() {
        const $form = this.$el.closest('form');
        if ($form.data('validator')) {
            $form.validate();
        }
    },

    /**
     * on collapse shown handler
     *
     * @param {jQuery.Event} event
     * @param {HTMLElement} event.target
     */
    onShow({target}) {
        this.checkValidation();

        if (target.getAttribute('data-role') === 'product-kit-form') {
            this.$el.addClass('show');
            mediator.trigger('layout:reposition');
            this.$('[data-type="secondary"]').collapse('hide');
        }
    },

    /**
     * On collapse hidden handler
     *
     * @param {jQuery.Event} event
     * @param {HTMLElement} event.target
     */
    onHide({target}) {
        this.checkValidation();

        if (target.getAttribute('data-role') === 'product-kit-form') {
            this.$el.removeClass('show');
            this.$('[data-type="secondary"]').collapse('show');
        }
    },

    updateFields() {
        Object.values(this.fields).forEach(({key, id}) => {
            const target = this.el.querySelector(`#${id}`);
            this.$(`[data-model="${key}"]`).text(this.getValueFromField(target));
        });
    },

    /**
     * On change input handler
     *
     * @param {jQuery.Event} event
     * @param {HTMLElement} event.target
     */
    onChangeInputs({target}) {
        const {name} = target;
        const {key} = this.getField(name);

        if (!key || !this.$(target).valid()) {
            return;
        }

        this.$(`[data-model="${key}"]`).text(this.getValueFromField(target));
    },

    /**
     * Check if have invalid fields
     */
    checkValidation() {
        const errors = this.$('.error');
        this.$el.toggleClass('has-error', !!errors.length);
    },

    /**
     * Get particular field with properties
     *
     * @param {string} targetName
     * @returns {Object}
     */
    getField(targetName) {
        return Object.values(this.fields).find(({name}) => targetName === name) || {};
    },

    /**
     * Format output value for append to DOM
     *
     * @param {HTMLElement} field
     * @returns {string}
     */
    getValueFromField(field) {
        if (this.$(field).data('formatted-value')) {
            return this.$(field).data('formatted-value');
        }

        if (field.type === 'checkbox') {
            return field.checked ? __('Yes') : __('No');
        }

        if (!field.value) {
            return __('N/A');
        }

        if (datetimeFormatter.isBackendDateTimeValid(field.value)) {
            return datetimeFormatter.formatDateTime(field.value);
        }

        if (datetimeFormatter.isBackendDateValid(field.value)) {
            return datetimeFormatter.formatDate(field.value);
        }

        if (field.getAttribute('type') === 'date') {
            return datetimeFormatter.formatDate(field.value);
        }

        return field.value;
    }
});

export default ProductKitView;
