define(function(require) {
    'use strict';

    var BackendSelectAllHeaderCell;
    var _  = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var template = require('tpl!oroproduct/templates/datagrid/backend-select-all-header-cell.html');
    var additionalTpl = require('tpl!oroproduct/templates/datagrid/backend-select-all-header-cell-short.html');
    var SelectAllHeaderCell = require('orodatagrid/js/datagrid/header-cell/select-all-header-cell');

    BackendSelectAllHeaderCell = SelectAllHeaderCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
        className: 'product-action',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.collection = options.collection;
            this.selectState = options.selectState;
            this.visibleState = options.visibleState;

            if (options.additionalTpl) {
                this.template = additionalTpl;
            }
            this.listenTo(this.selectState, 'change', _.bind(_.debounce(this.updateState, 50), this));
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function(events) {
            this.$('[data-checkbox-change-visible]')
                .on('change' + this.eventNamespace(), _.bind(this.onCheckboxChange, this));
            this.$('[data-select-unbind]')
                .on('click' + this.eventNamespace(), _.bind(this.onSelectUnbind, this));

            this.collection.on('backgrid:visible-changed', _.bind(_.debounce(this.unCheckCheckbox, 50), this));

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
            var checked = $(event.currentTarget).is(':checked');

            mediator.trigger('popupGalleryWidget:toggle', !checked);

            if (!checked) {
                this.collection.trigger('backgrid:selectNone');
            }

            this.visibleState.visible = !checked;

            this.canTSelect(!checked);
        },

        onSelectUnbind: function() {
            this.collection.trigger('backgrid:selectNone');
            this.collection.trigger('backgrid:visible-changed');
            this.canTSelect(true);
        },

        canTSelect: function(flag) {
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
