define(function (require) {
    'use strict';

    var TreeViewComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var tools = require('oroui/js/tools');
    var BasicTreeComponent = require('orob2bcatalog/js/app/components/basic-tree-component');

    /**
     * Options:
     * - defaultCategoryId - default selected category id
     *
     * @export orob2bcatalog/js/app/components/tree-manage-component
     * @extends orob2bcatalog.app.components.BasicTreeComponent
     * @class orob2bcatalog.app.components.TreeViewComponent
     */
    TreeViewComponent = BasicTreeComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            sidebarAlias: 'products-sidebar'
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            TreeViewComponent.__super__.initialize.call(this, options);
            if (!this.$tree) {
                return;
            }

            this.$tree.on('ready.jstree', _.bind(function() {
                this.$tree.jstree('select_node', Number(options.defaultCategoryId));
                this.$tree.on('select_node.jstree', _.bind(this.onSelect, this));
            }, this));

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
                config['dnd'] = {
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
        onSelect: function(node, selected) {
            if (this.initialization) {
                return;
            }

            this.triggerSidebarChanged(selected);
        },

        /**
         * @param {Object} selected
         */
        triggerSidebarChanged: function(selected) {
            var params = {
                categoryId: selected.node.id
            };

            mediator.trigger(
              'grid-sidebar:change:' + this.options.sidebarAlias,
              {widgetReload: Boolean(true), params: params}
            );
        }
    });

    return TreeViewComponent;
});
