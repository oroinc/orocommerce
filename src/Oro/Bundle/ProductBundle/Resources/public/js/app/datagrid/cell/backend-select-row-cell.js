define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const template = require('tpl-loader!oroproduct/templates/datagrid/backend-select-row-cell.html');
    const SelectRowCell = require('oro/datagrid/cell/select-row-cell');

    /**
     * Renders a checkbox for row selection.
     *
     * @export  oro/datagrid/cell/select-row-cell
     * @class   oro.datagrid.cell.SelectRowCell
     * @extends BaseView
     */
    const BackendSelectRowCell = SelectRowCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        /** @property */
        checkboxSelector: '[data-role="select-row-cell"]',

        /** @property */
        text: __('oro.product.grid.select_product'),

        /**
         * @inheritdoc
         */
        constructor: function BackendSelectRowCell(options) {
            BackendSelectRowCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const o = {};
            if (options.productModel) {
                this.model = options.productModel;
            }

            this.model.trigger('backgrid:hasMassActions', o);

            if (!o.hasMassActions) {
                this.dispose();

                return;
            }

            this.$container = $(options._sourceElement);
            this.template = this.getTemplateFunction();

            this.model.on('backgrid:select', _.bind(function(model, checked) {
                this.$(':checkbox').prop('checked', checked).change();
            }, this));

            this.model.on('backgrid:canSelected', _.bind(function(checked) {
                this.hideView(checked);
            }, this));
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const visibleState = {};
            let hide = !_.isMobile();
            const state = {selected: false};

            this.model.trigger('backgrid:isSelected', this.model, state);
            this.model.trigger('backgrid:getVisibleState', visibleState);
            if (!_.isEmpty(visibleState)) {
                hide = visibleState.visible;
            }

            this.$el.html(this.template({
                checked: state.selected,
                text: this.text
            }));

            this.$checkbox = this.$el.find(this.checkboxSelector);
            this.$container.append(this.$el);
            this.hideView(hide);

            return this;
        },

        /**
         * @param {Boolean} bool
         */
        hideView: function(bool) {
            this.$el.toggleClass('hidden', !bool);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.hide;
            delete this.$container;

            if (this.model) {
                this.model.off(null, null, this);
            }
            BackendSelectRowCell.__super__.dispose.call(this);
        }
    });

    return BackendSelectRowCell;
});
