define(function(require) {
    'use strict';

    var BackendSelectRowCell;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var template = require('tpl!oroproduct/templates/datagrid/backend-select-row-cell.html');
    var SelectRowCell = require('oro/datagrid/cell/select-row-cell');

    /**
     * Renders a checkbox for row selection.
     *
     * @export  oro/datagrid/cell/select-row-cell
     * @class   oro.datagrid.cell.SelectRowCell
     * @extends BaseView
     */
    BackendSelectRowCell = SelectRowCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
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
            this.$container = $(options._sourceElement);

            if (options.productModel) {
                this.model = options.productModel;
            }

            this.template = this.getTemplateFunction();

            this.model.on('backgrid:select', _.bind(function(model, checked) {
                this.$(':checkbox').prop('checked', checked).change();
            }, this));

            this.model.on('backgrid:canSelected', _.bind(function(checked) {
                this.hideView(checked);
            }, this));
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var visibleState = {};
            var state = {selected: false};

            this.model.trigger('backgrid:isSelected', this.model, state);
            this.model.trigger('backgrid:getVisibleState', visibleState);

            this.$el.html(this.template({
                checked: state.selected,
                text: this.text
            }));

            this.$checkbox = this.$el.find(this.checkboxSelector);

            this.$container.append(this.$el);

            this.hideView(visibleState.visible);

            return this;
        },

        /**
         * @param {Boolean} bool
         */
        hideView: function(bool) {
            this.$el[bool ? 'addClass' : 'removeClass']('hidden');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.hide;
            BackendSelectRowCell.__super__.dispose.apply(this, arguments);
        }
    });

    return BackendSelectRowCell;
});
