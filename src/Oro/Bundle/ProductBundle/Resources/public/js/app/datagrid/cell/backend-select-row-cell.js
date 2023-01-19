define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const template = require('tpl-loader!oroproduct/templates/datagrid/backend-select-row-cell.html');
    const SelectRowCell = require('oro/datagrid/cell/select-row-cell');
    const viewportManager = require('oroui/js/viewport-manager').default;

    const modes = {
        DROPDOWN: 'Dropdown',
        SIMPLE: 'Simple'
    };

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

        listen() {
            return {
                [`viewport:${this.getScreenSize()} mediator`]: 'defineRenderingStrategy'
            };
        },

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
            this.model.on('backgrid:canSelected', checked => {
                this.hideView(!checked && this._isSimple());
            });
        },

        defineRenderingStrategy() {
            const prevRenderMode = this.renderMode;

            if (this._isSimple()) {
                this.renderMode = modes.SIMPLE;
            } else {
                this.renderMode = modes.DROPDOWN;
            }

            if (prevRenderMode !== this.renderMode) {
                this.trigger('render-mode:changed', {
                    prevRenderMode,
                    renderMode: this.renderMode
                });
            }
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const visibleState = {};
            let hideCheckboxes = this._isSimple();
            const state = {selected: false};

            this.model.trigger('backgrid:isSelected', this.model, state);
            this.model.trigger('backgrid:getVisibleState', visibleState);
            if (!_.isEmpty(visibleState)) {
                // Mobile view row selection is turned on
                hideCheckboxes = !visibleState.visible;
            }

            this.$el.html(this.template({
                checked: state.selected,
                text: this.text,
                isSimple: this._isSimple()
            }));

            this.$checkbox = this.$el.find(this.checkboxSelector);
            this.$container.append(this.$el);
            this.hideView(hideCheckboxes);

            return this;
        },

        _isSimple() {
            return viewportManager.isApplicable(this.getScreenSize());
        },

        getScreenSize() {
            let screen = 'tablet';

            try {
                const resolution = this.model.collection.options.optimizedScreenSize;
                if (resolution) {
                    screen = resolution;
                }
            } catch (e) {}

            return screen;
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
