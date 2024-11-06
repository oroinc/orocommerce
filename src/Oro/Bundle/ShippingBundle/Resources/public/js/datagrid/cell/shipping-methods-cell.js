import $ from 'jquery';
import StringCell from 'oro/datagrid/cell/string-cell';
import NumberFormatter from 'orolocale/js/formatter/number';
import mediator from 'oroui/js/mediator';
import template from 'tpl-loader!oroshipping/templates/datagrid/cell/shipping-methods-cell.html';
import multiShippingMethodsSelect2Template
    from 'tpl-loader!oroshipping/templates/multi-shipping-methods-select2-template.html';

import moduleConfig from 'module-config';
const config = {
    ...moduleConfig(module.id)
};

function comparator(a, b) {
    return a.sortOrder - b.sortOrder;
}

const ShippingMethodsCell = StringCell.extend({
    template: template,

    events() {
        // events property should be a function to skip events delegation to the TR element
        // see `'orodatagrid/js/datagrid/cell-event-list'`
        return {
            'change [data-role="select-shipping-method"]': 'onChange'
        };
    },

    _attributes() {
        if (!this.model.get(this.column.get('name'))) {
            return {
                'aria-label': null,
                'data-blank-content': null,
                'aria-colindex': null
            };
        }
    },

    titleClassName: config.titleClassName,

    constructor: function ShippingMethodsCell(options) {
        ShippingMethodsCell.__super__.constructor.call(this, options);
    },

    onChange(e) {
        const data = $(e.target).inputWidget('data');
        const methodType = $(data.element);
        const method = methodType.data('shipping-method');
        const type = methodType.data('shipping-type');
        const itemId = methodType.data('item-id');
        mediator.trigger('multi-shipping-method:changed', itemId, method, type);
    },

    getTemplateData() {
        const {shippingMethods = {}, ...data} = this.model.toJSON();
        if (!data.lineItemId) {
            data.lineItemId = data.id;
        }
        data.formatter = NumberFormatter;
        data.shippingMethods = Object.values(shippingMethods)
            .map(method => {
                method.types = Object.values(method.types || {}).sort(comparator);
                return method;
            })
            .sort(comparator);
        data._metadata = {
            ...this.column.get('metadata')
        };
        data.titleClassName = this.titleClassName;
        return data;
    },

    render() {
        this.$el.html(this.template(this.getTemplateData()));

        const selectFormat = state => {
            return multiShippingMethodsSelect2Template({
                ...state,
                ...$(state.element).data(),
                formatter: NumberFormatter
            });
        };

        this.$('[data-role="select-shipping-method"]').inputWidget('create', 'select2', {
            initializeOptions: {
                minimumResultsForSearch: -1,
                formatSelection: selectFormat,
                formatResult: selectFormat
            }
        });

        return this;
    }
});

export default ShippingMethodsCell;
