define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const template = require('tpl-loader!oroproduct/templates/datagrid/backend-select-all-header-cell.html');
    const additionalTpl = require('tpl-loader!oroproduct/templates/datagrid/backend-select-all-header-cell-short.html');
    const SelectAllHeaderCell = require('orodatagrid/js/datagrid/header-cell/select-all-header-cell');

    const BackendSelectAllHeaderCell = SelectAllHeaderCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
        className: 'product-action',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        /**
         * @inheritdoc
         */
        constructor: function BackendSelectAllHeaderCell(options) {
            BackendSelectAllHeaderCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.collection = options.collection;
            this.selectState = options.selectState;

            if (options.additionalTpl) {
                this.template = additionalTpl;
            }
        },

        /**
         * @inheritdoc
         */
        delegateEvents: function(events) {
            this.$('[data-checkbox-change-visible]')
                .on('change' + this.eventNamespace(), _.bind(_.debounce(this.onCheckboxChange, 50), this));
            this.$('[data-select-unbind]')
                .on('click' + this.eventNamespace(), _.bind(_.debounce(this.onSelectUnbind, 50), this));

            this.collection.on('backgrid:visible-changed', _.bind(_.debounce(this.unCheckCheckbox, 50), this));
            this.listenTo(this.selectState, 'change', _.bind(_.debounce(this.updateState, 50), this));

            BackendSelectAllHeaderCell.__super__.delegateEvents.call(this, events);
        },

        onCheckboxClick: function(e) {
            if (this.selectState.get('inset') && this.selectState.isEmpty()) {
                this.collection.trigger('backgrid:selectAllVisible');
            } else {
                this.collection.trigger('backgrid:selectNone');
            }
            e.stopPropagation();
        },

        onCheckboxChange: function(event) {
            const checked = $(event.currentTarget).is(':checked');

            if (!checked) {
                this.collection.trigger('backgrid:selectNone');
            }

            this.collection.trigger('backgrid:selectNone');
            this.collection.trigger('backgrid:setVisibleState', checked);

            this.canSelect(checked);
        },

        onSelectUnbind: function() {
            this.collection.trigger('backgrid:selectNone');
            this.collection.trigger('backgrid:visible-changed');
            this.canSelect(false);
        },

        canSelect: function(flag) {
            this.collection.each(function(model) {
                model.trigger('backgrid:canSelected', flag);
            });
        },

        unCheckCheckbox: function() {
            this.$('[data-checkbox-change-visible]')
                .prop('checked', false)
                .parent()
                .removeClass('checked');
        }
    });

    return BackendSelectAllHeaderCell;
});
