import $ from 'jquery';
import InputCellValidationEditor from 'orodatagrid/js/datagrid/editor/input-cell-validation-editor';

const SORT_ORDER_PREFIX = 'sortOrder_';
const SORT_ORDER_DISABLED_PREFIX = 'disabledSortOrder_';

const OrderInputValidationEditor = InputCellValidationEditor.extend({
    constructor: function OrderInputValidationEditor(attributes, options) {
        return OrderInputValidationEditor.__super__.constructor.call(this, attributes, options);
    },

    attributes() {
        return {
            'type': 'text',
            'name': `${SORT_ORDER_PREFIX}${this.model.cid}`,
            'data-validation': JSON.stringify(this.model.get('constraints')),
            'data-floating-error': ''
        };
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        OrderInputValidationEditor.__super__.initialize.call(this, options);

        const columnName = this.model.get('columnName');
        this.listenTo(this.model, `change:${columnName}`, (model, isActive) => {
            if (this.model.cid === model.cid) {
                this.onBackgridSelected(isActive);
            }
        });
        this._toggleInput(this.model.get(columnName));
    },

    /**
     * @param {boolean} isActive
     */
    onBackgridSelected(isActive) {
        this._toggleInput(isActive);
    },

    /**
     * @inheritdoc
     */
    saveOrCancel(e) {
        // Do nothing if input field is hidden
        if (!$(e.target).is(':visible')) {
            return;
        }

        return OrderInputValidationEditor.__super__.saveOrCancel.call(this, e);
    },

    /**
     * Show or hide input editor
     * @param {boolean} show = true
     * @private
     */
    _toggleInput(show = true) {
        if (show) {
            this.$el.attr({
                name: `${SORT_ORDER_PREFIX}${this.model.cid}`,
                disabled: null
            }).show();
            this.validateElement();
        } else {
            this.resetValidation();
            this.$el.attr({
                name: `${SORT_ORDER_DISABLED_PREFIX}${this.model.cid}`,
                disabled: true
            }).hide();
        }
    }
});

export default OrderInputValidationEditor;
