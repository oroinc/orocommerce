define(function(require) {
    'use strict';

    var BackendToolbar;
    var $ = require('jquery');
    var _ = require('underscore');
    var Toolbar = require('orodatagrid/js/datagrid/toolbar');
    var BackendPagination = require('oroproduct/js/app/datagrid/backend-pagination-input');
    var BackendPageSize = require('oroproduct/js/app/datagrid/backend-page-size');
    var BackendSortingDropdown = require('oroproduct/js/app/datagrid/sorting/backend-dropdown');

    BackendToolbar = Toolbar.extend({
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
         *  @inheritDoc
         */
        initialize: function(options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            this.collection = options.collection;

            var optionsPagination = _.defaults({collection: this.collection}, options.pagination);
            var optionsPageSize = _.defaults({collection: this.collection}, options.pageSize);
            var optionsSorting =  _.defaults({collection: this.collection, columns: options.columns}, options.sortingDropdown);

            options.columns.trigger('configureInitializeOptions', this.pagination, optionsPagination, this);
            options.columns.trigger('configureInitializeOptions', this.pageSize, optionsPageSize, this);
            options.columns.trigger('configureInitializeOptions', this.sortingDropdown, optionsSorting, this);

            options.pagination = optionsPagination;
            options.pageSize = optionsPageSize;
            options.addSorting = optionsSorting;

            BackendToolbar.__super__.initialize.call(this, options);
        },

        /**
         *  @inheritDoc
         */
        render: function() {
            var $pagination;

            $pagination = this.subviews.pagination.render().$el;
            $pagination.attr('class', this.$(this.selector.pagination).attr('class'));

            this.$(this.selector.pagination).replaceWith($pagination);
           // this.$(this.selector.pagesize).append(this.subviews.pageSize.render().$el);
            this.$(this.selector.actionsPanel).append(this.subviews.actionsPanel.render().$el);

            this.$(this.selector.itemsCounter).replaceWith(this.subviews.itemsCounter.render().$el);
            this.subviews.itemsCounter.$el.hide();

            if (this.subviews.sortingDropdown) {
                this.$(this.selector.sortingDropdown).append(this.subviews.sortingDropdown.render().$el);
            }

            if (this.subviews.extraActionsPanel.haveActions()) {
                this.$(this.selector.extraActionsPanel).append(this.subviews.extraActionsPanel.render().$el);
            } else {
                this.$(this.selector.extraActionsPanel).hide();
            }

            return this;
        }
    });

    return BackendToolbar;
});
