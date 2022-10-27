define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Toolbar = require('orodatagrid/js/datagrid/toolbar');
    const BackendPagination = require('oroproduct/js/app/datagrid/backend-pagination-input');
    const BackendPageSize = require('oroproduct/js/app/datagrid/backend-page-size');
    const BackendSortingDropdown = require('oroproduct/js/app/datagrid/sorting/backend-dropdown');

    const BackendToolbar = Toolbar.extend({
        /** @property */
        template: null,

        /** @property */
        pagination: BackendPagination,

        /** @property */
        pageSize: BackendPageSize,

        /** @property */
        sortingDropdown: BackendSortingDropdown,

        /** @property */
        themeOptions: {
            optionPrefix: 'backendtoolbar'
        },

        /**
         * @inheritdoc
         */
        constructor: function BackendToolbar(options) {
            BackendToolbar.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            this.collection = options.collection;

            const optionsPagination = _.defaults({collection: this.collection}, options.pagination);
            const optionsPageSize = _.defaults({collection: this.collection}, options.pageSize);

            options.columns.trigger('configureInitializeOptions', this.pagination, optionsPagination, this);
            options.columns.trigger('configureInitializeOptions', this.pageSize, optionsPageSize, this);

            options.pagination = optionsPagination;
            options.pageSize = optionsPageSize;

            if (options.className) {
                this.$el.addClass(options.className);
            }
            BackendToolbar.__super__.initialize.call(this, options);
        },

        /**
         *  @inheritdoc
         */
        render: function() {
            let $pagination;

            if (this.subviews.pagination) {
                $pagination = this.subviews.pagination.render().$el;
                $pagination.attr('class', this.$(this.selector.pagination).attr('class'));
                this.$(this.selector.pagination).replaceWith($pagination);
            }

            if (this.subviews.pageSize) {
                this.$(this.selector.pagesize).append(this.subviews.pageSize.render().$el);
            }

            if (this.subviews.actionsPanel) {
                this.$(this.selector.actionsPanel).append(this.subviews.actionsPanel.render().$el);
            }

            if (this.subviews.itemsCounter) {
                this.$(this.selector.itemsCounter).append(this.subviews.itemsCounter.render().$el);
            }

            if (this.subviews.sortingDropdown) {
                this.$(this.selector.sortingDropdown).append(this.subviews.sortingDropdown.render().$el);
            }

            if (this.subviews.extraActionsPanel) {
                if (this.subviews.extraActionsPanel.haveActions()) {
                    this.$(this.selector.extraActionsPanel).append(this.subviews.extraActionsPanel.render().$el);
                } else {
                    this.$(this.selector.extraActionsPanel).hide();
                }
            }

            return this;
        }
    });

    return BackendToolbar;
});
