define(function(require) {
    'use strict';

    const $ = require('jquery');
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
        text: null,

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
            const data = {};
            if (options.productModel) {
                this.model = options.productModel;
            }

            this.model.trigger('backgrid:hasMassActions', data);

            if (!data.hasMassActions) {
                this.dispose();

                return;
            }

            this.$container = $(options._sourceElement);
            this.template = this.getTemplateFunction();

            this.listenTo(this, 'render-mode:changed', state => this.render());
            this.model.on('backgrid:select', (model, checked) => {
                this.$(':checkbox').prop('checked', checked).change();
            });
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const visibleState = {};
            const state = {selected: false};

            this.model.trigger('backgrid:isSelected', this.model, state);
            this.model.trigger('backgrid:getVisibleState', visibleState);

            this.$el.html(this.template({
                checked: state.selected,
                text: this.text
            }));

            this.$checkbox = this.$el.find(this.checkboxSelector);
            this.$container.append(this.$el);

            return this;
        },

        /**
         * @param {Boolean} isHidden
         */
        hideView: function(isHidden) {
            this.$el.toggleClass('hidden', isHidden);
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
