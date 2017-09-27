define(function(require) {
    'use strict';

    var BackendSelectRowCell;
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var template = require('tpl!orodatagrid/templates/datagrid/select-row-cell.html');
    var SelectRowCell = require('oro/datagrid/cell/select-row-cell');

    /**
     * Renders a checkbox for row selection.
     *
     * @export  oro/datagrid/cell/select-row-cell
     * @class   oro.datagrid.cell.SelectRowCell
     * @extends BaseView
     */
    BackendSelectRowCell = SelectRowCell.extend({
        keepElement: true,

        /** @property */
        className: 'product-item__select-row',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        /** @property */
        checkboxSelector: '[data-role="select-row-cell"]',

        /** @property */
        text: __('oro.product.grid.select_product'),

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (options.productModel) {
                this.model = options.productModel;
            }
            this.$container = $(options._sourceElement);

            this.template = this.getTemplateFunction();

            this.listenTo(this.model, 'backgrid:select', function(model, checked) {
                this.$(':checkbox').prop('checked', checked).change();
            });

            this.render();
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var state = {selected: false};

            this.model.trigger('backgrid:isSelected', this.model, state);
            this.$el.html(this.template({
                checked: state.selected,
                text: this.text
            }));
            this.$checkbox = this.$el.find(this.checkboxSelector);

            this.$container.append(this.$el);
            return this;
        }
    });

    return BackendSelectRowCell;
});
