define(function(require) {
    'use strict';

    var ProductSidebarView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseTreeView = require('oroui/js/app/views/jstree/base-tree-view');

    ProductSidebarView = BaseTreeView.extend({
        /**
         * @property {Object}
         */
        selectedCategoryId: null,

        /**
         * @property {Object}
         */
        options: {
            defaultCategoryId: null,
            sidebarAlias: 'products-sidebar',
            includeSubcategoriesSelector: '.include-sub-categories-choice input[type=checkbox]'
        },

        /**
         * @property {jQuery.Element}
         */
        subcategoriesSelector: null,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            ProductSidebarView.__super__.initialize.call(this, options);
            if (!this.$tree) {
                return;
            }

            this.selectedCategoryId = String(options.defaultCategoryId);
            this.$tree.jstree('select_node', this.selectedCategoryId);
            this.$tree.on('select_node.jstree', _.bind(this.onCategorySelect, this));

            this.subcategoriesSelector = $(this.options.includeSubcategoriesSelector);
            this.subcategoriesSelector.on('change', _.bind(this.onIncludeSubcategoriesChange, this));
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
                    'is_draggable': false
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
            this.triggerSidebarChanged();
        },

        triggerSidebarChanged: function() {
            var params = {
                categoryId: this.selectedCategoryId,
                includeSubcategories: this.subcategoriesSelector.prop('checked')
            };

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
        }
    });

    return ProductSidebarView;
});
