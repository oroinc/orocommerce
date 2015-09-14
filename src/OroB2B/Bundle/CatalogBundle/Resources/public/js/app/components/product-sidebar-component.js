define(function(require) {
    'use strict';

    var ProductSidebarComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BasicTreeComponent = require('orob2bcatalog/js/app/components/basic-tree-component');

    /**
     * Options:
     * - defaultCategoryId - default selected category id
     *
     * @export orob2bcatalog/js/app/components/tree-manage-component
     * @extends orob2bcatalog.app.components.BasicTreeComponent
     * @class orob2bcatalog.app.components.ProductSidebarComponent
     */
    ProductSidebarComponent = BasicTreeComponent.extend({
        /**
         * @property {Object}
         */
        selectedCategoryId: 1,

        /**
         * @property {Object}
         */
        options: {
            sidebarAlias: 'products-sidebar',
            includeSubcategoriesSelector: '.include-sub-categories-choice input[type=checkbox]'
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            ProductSidebarComponent.__super__.initialize.call(this, options);
            if (!this.$tree) {
                return;
            }

            this.$tree.on('ready.jstree', _.bind(function() {
                this.$tree.jstree('select_node', Number(options.defaultCategoryId));
                this.$tree.on('select_node.jstree', _.bind(this.onCategorySelect, this));
            }, this));

            $(this.options.includeSubcategoriesSelector).on('change', _.bind(this.onIncludeSubcategoriesChange, this))

            this._fixContainerHeight();
        },

        /**
         * @param {Object} options
         * @param {Object} config
         * @returns {Object}
         */
        customizeTreeConfig: function(options, config) {
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
            this.selectedCategoryId = selected.node.id;
            this.triggerSidebarChanged();
        },

        onIncludeSubcategoriesChange: function() {
            this.triggerSidebarChanged();
        },

        triggerSidebarChanged: function() {
            var params = {
                categoryId: this.selectedCategoryId,
                includeSubcategories: $(this.options.includeSubcategoriesSelector).prop('checked')
            };

            mediator.trigger(
                'grid-sidebar:change:' + this.options.sidebarAlias,
                {params: params}
            );
        }
    });

    return ProductSidebarComponent;
});
