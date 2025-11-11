import $ from 'jquery';
import template from 'tpl-loader!oroproduct/default/templates/filter/inventory-switcher-filter.html';
import MultiselectFilter from 'orofrontend/js/app/views/frontend-multiselect-filter';
import InventoryFilterHintView from 'orofrontend/default/js/app/views/inventory-filter-hint-view';
import KEYBOARD_CODES from 'oroui/js/tools/keyboard-key-codes';

const FrontendInventorySwitchFilter = MultiselectFilter.extend({
    /**
     * Filter selector template
     *
     * @property
     */
    template: template,

    HintView: InventoryFilterHintView,

    events: {
        'change [data-role="inventory-checkbox"]': 'onCheckboxValueChange',
        'keydown .filter-criteria-selector': 'onCriteriaToggle'
    },

    /**
     * @property {Object}
     */
    listen: {
        'filters-manager:after-applying-state mediator': 'updateVisibility'
    },

    /**
     * @inheritdoc
     */
    filterEnableValueBadge: false,

    /**
     * @inheritdoc
     */
    allowClearButtonInFilter: false,

    /**
     * @inheritdoc
     */
    enableMultiselectWidget: false,

    /**
     * @inheritdoc
     */
    setDropdownContainer: void 0,

    wrappable: false,

    /**
     * @inheritdoc
     */
    constructor: function FrontendInventorySwitchFilter(options) {
        FrontendInventorySwitchFilter.__super__.constructor.call(this, options);
    },

    /**
     * Compares current value with empty value
     *
     * @return {Boolean}
     */
    isEmpty() {
        // Switch can not be empty
        return false;
    },

    render() {
        this.resetFlags();

        // render only wrapper (a button and a dropdown container e.g.)
        this._renderCriteria();
        this._updateDOMValue();

        return this;
    },

    toggleCheckbox() {
        this.$('[data-role="inventory-checkbox"]').prop('checked', (i, checked) => !checked).trigger('change');
    },

    onCheckboxValueChange(e) {
        const {value, checked} = e.currentTarget;
        const $select = this.$('select');

        $select.val(checked ? value : '').trigger('change');
    },

    updateVisibility() {
        // Always display a filter if it has a selected value to have the option to clear it
        if (this._getSelectedChoices(this._getDisplayValue(), this.choices).length) {
            this.visible = true;
        }
    },

    _renderCriteria() {
        const $filter = $(this.template(this.getTemplateData()));
        this._appendFilter($filter);
        this._criteriaRenderd = true;
        this._isRenderingInProgress = false;
    },

    /**
     * Append filter to its place
     *
     * @param {Element|jQuery|string} $filter
     * @private
     */
    _appendFilter($filter) {
        this.setElement($filter);
    },

    _onClickCriteriaSelector() {},

    onCriteriaToggle(e) {
        if (
            e.keyCode === KEYBOARD_CODES.ENTER ||
            e.keyCode === KEYBOARD_CODES.SPACE
        ) {
            e.preventDefault();
            this.toggleCheckbox();
        }
    },

    reset() {
        FrontendInventorySwitchFilter.__super__.reset.call(this);
        this._updateDOMValue();
    },

    _updateDOMValue() {
        FrontendInventorySwitchFilter.__super__._updateDOMValue.call(this);
        this._updateValueField();
    },

    _updateValueField() {
        const $checkbox = this.$('[data-role="inventory-checkbox"]');

        $checkbox.prop('checked', $checkbox.val() === this.getSelectedValue()?.value[0]);
    },

    getSelectedValue() {
        return Object.assign({}, this.emptyValue, this.value);
    },

    /**
     * A filter does not have a count label, so no need to modify data
     *
     * @override 'orofrontend/js/app/filter-count-helper'
     * @param {Object} data
     * @returns {Object}
     */
    filterTemplateData(data) {
        return data;
    },

    getCriteriaSelector() {
        return this.$('[data-role="inventory-checkbox"]');
    },

    /**
     * @override 'orofrontend/js/app/filter-count-helper'
     */
    rerenderFilter() {},

    toggleFilter() {},

    _initializeSelectWidget() {},

    _showCriteria() {},

    _hideCriteria() {},

    _appendToContainer() {},

    _setDropdownWidth() {}
});

export default FrontendInventorySwitchFilter;
