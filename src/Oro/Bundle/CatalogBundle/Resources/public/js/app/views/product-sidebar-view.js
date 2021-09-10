define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseTreeView = require('oroui/js/app/views/jstree/base-tree-view');
    const tools = require('oroui/js/tools');

    const ProductSidebarView = BaseTreeView.extend({
        /**
         * @property {Object}
         */
        selectedCategoryId: null,

        /**
         * @property {Object}
         */
        listen: {
            'grid_load:complete mediator': 'onGridLoadComplete'
        },

        /**
         * @property {Object}
         */
        options: {
            defaultCategoryId: null,
            sidebarAlias: 'products-sidebar',
            includeSubcategoriesSelector: '.include-sub-categories-choice input[type=checkbox]',
            includeNotCategorizedProductSelector: '.include-not-categorized-product-choice input[type=checkbox]'
        },

        /**
         * @property {jQuery.Element}
         */
        subcategoriesSelector: null,

        /**
         * @property {String}
         */
        gridName: null,

        /**
         * @inheritdoc
         */
        constructor: function ProductSidebarView(options) {
            ProductSidebarView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            ProductSidebarView.__super__.initialize.call(this, options);
            if (!this.$tree) {
                return;
            }

            this.selectedCategoryId = options.defaultCategoryId;
            this.$tree.jstree('select_node', this.selectedCategoryId);
            this.$tree.on('select_node.jstree', this.onCategorySelect.bind(this));

            this.subcategoriesSelector = $(this.options.includeSubcategoriesSelector);
            this.notCategorizedProductSelector = $(this.options.includeNotCategorizedProductSelector);
            this.subcategoriesSelector.on('change', this.onIncludeSubcategoriesChange.bind(this));
            this.notCategorizedProductSelector.on('change', this.onIncludeNonCategorizedProductChange.bind(this));

            mediator.on('import-export:handleExport', this.onHandleExport, this);
        },

        onGridLoadComplete: function(collection) {
            this.gridName = collection.options.gridName;
        },

        /**
         * @param {Object} options
         * @param {Object} config
         * @returns {Object}
         */
        customizeTreeConfig: function(options, config) {
            ProductSidebarView.__super__.customizeTreeConfig.call(this, options, config);
            if (options.updateAllowed) {
                config.plugins.push('dnd');
                config.dnd = {
                    is_draggable: false
                };
            }

            return config;
        },

        /**
         * Triggers after category selection in tree
         *
         * @param {Object} node
         * @param {Object} selected
         */
        onCategorySelect: function(node, selected) {
            if (this.initialization) {
                return;
            }

            if (selected.node.id === this.selectedCategoryId) {
                this.$tree.jstree('deselect_node', selected.node);
                this.selectedCategoryId = null;
            } else {
                this.selectedCategoryId = selected.node.id;
            }
            this.triggerSidebarChanged();
        },

        onIncludeSubcategoriesChange: function() {
            if (this.selectedCategoryId) {
                this.triggerSidebarChanged();
            }
        },

        onIncludeNonCategorizedProductChange: function() {
            this.triggerSidebarChanged();
        },

        /**
         * @param exportRouteOptions
         */
        onHandleExport: function(exportRouteOptions) {
            const queryParams = tools.unpackFromQueryString(window.location.search);

            if (queryParams.hasOwnProperty('categoryId')) {
                exportRouteOptions.categoryId = queryParams['categoryId'];
            }
            if (queryParams.hasOwnProperty('includeSubcategories')) {
                exportRouteOptions.includeSubcategories = queryParams['includeSubcategories'];
            }
            if (queryParams.hasOwnProperty('includeNotCategorizedProducts')) {
                exportRouteOptions.includeNotCategorizedProducts = queryParams['includeNotCategorizedProducts'];
            }
        },

        triggerSidebarChanged: function() {
            const params = {
                categoryId: this.selectedCategoryId ? this.selectedCategoryId : 0,
                includeSubcategories: this.subcategoriesSelector.prop('checked') ? 1 : 0,
                includeNotCategorizedProducts: this.notCategorizedProductSelector.prop('checked') ? 1 : 0
            };

            params[this.gridName] = _.clone(params);

            mediator.trigger(
                'grid-sidebar:change:' + this.options.sidebarAlias,
                {params: params}
            );
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$tree
                .off('select_node.jstree')
                .off('ready.jstree');

            this.subcategoriesSelector.off('change');
            ProductSidebarView.__super__.dispose.call(this);

            mediator.off('import-export:handleExport', this.onHandleExport, this);
        }
    });

    return ProductSidebarView;
});
