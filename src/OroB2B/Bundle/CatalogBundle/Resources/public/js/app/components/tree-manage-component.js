define(function (require) {
    'use strict';

    var TreeManageComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        layout = require('oroui/js/layout'),
        messenger = require('oroui/js/messenger'),
        BasicTreeComponent = require('orob2bcatalog/js/app/components/basic-tree-component');

    TreeManageComponent = BasicTreeComponent.extend({
        /**
         * @property {Boolean}
         */
        moveTriggered : false,

        /**
         * @param {Object} options
         */
        initialize: function (options) {
            TreeManageComponent.__super__.initialize.call(this, options);
            if (!this.$tree) {
                return;
            }

            this.$tree.on('select_node.jstree', _.bind(this.onSelect, this));
            this.$tree.on('move_node.jstree', _.bind(this.onMove, this));

            this._fixContainerHeight();
        },

        /**
         * @param {Object} options
         * @param {Object} config
         * @returns {Object}
         */
        customizeTreeConfig: function(options, config) {
            if (options.dndEnabled) {
                config.plugins.push('dnd');
                config['dnd'] = {
                    'copy' : false
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

            var url = Routing.generate('orob2b_catalog_category_update', {id: selected.node.id});
            mediator.execute('redirectTo', {url: url});
        },
        
        /**
         * Triggers after category move
         *
         * @param {Object} e
         * @param {Object} data
         */
        onMove: function(e, data) {
            if (this.moveTriggered) {
                return;
            }

            if (data.parent == '#') {
                this.rollback(data);
                messenger.notificationFlashMessage('warning', __("orob2b.catalog.add_new_root_warning"));
                return;
            }

            var self = this;
            $.ajax({
                async: false,
                type: 'PUT',
                url: Routing.generate('orob2b_catalog_category_move'),
                data: {
                    id: data.node.id,
                    parent: data.parent,
                    position: data.position
                },
                success: function (result) {
                    if (!result.status) {
                        self.rollback(data);
                        messenger.notificationFlashMessage(
                            'error',
                            __("orob2b.catalog.move_category_error", {nodeText: data.node.text})
                        );
                    }
                }
            });
        },

        /**
         * Rollback category move
         *
         * @param {Object} data
         */
        rollback: function(data) {
            this.moveTriggered = true;
            this.$tree.jstree('move_node', data.node, data.old_parent, data.old_position);
            this.moveTriggered = false;
        },
        
        /** 
         * Fix scrollable container height
         * TODO: This method should be removed during fixing of https://magecore.atlassian.net/browse/BB-336
         *
         * @private
         */
        _fixContainerHeight: function() {
            var categoryTree = this.$tree.parent();
            if (!categoryTree.hasClass('category-tree')) {
                return;
            }

            var categoryContainer = categoryTree.parent();
            if (!categoryContainer.hasClass('category-container')) {
                return;
            }

            var fixHeight = function() {
                var anchor = $('#bottom-anchor').position().top;
                var container = categoryContainer.position().top;
                var debugBarHeight = $('.sf-toolbar:visible').height() || 0;
                var footerHeight = $('#footer:visible').height() || 0;
                var fixContent = 1;

                categoryTree.height(anchor - container - debugBarHeight - footerHeight + fixContent);
            };

            layout.onPageRendered(fixHeight);
            $(window).on('resize', _.debounce(fixHeight, 50));
            mediator.on("page:afterChange", fixHeight);
            mediator.on('layout:adjustReloaded', fixHeight);
            mediator.on('layout:adjustHeight', fixHeight);

            fixHeight();
        }
    });

    return TreeManageComponent;
});
