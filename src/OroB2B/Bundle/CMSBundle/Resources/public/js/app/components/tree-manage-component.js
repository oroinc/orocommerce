define(function (require) {
    'use strict';

    var TreeManageComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        widgetManager = require('oroui/js/widget-manager'),
        mediator = require('oroui/js/mediator'),
        layout = require('oroui/js/layout'),
        messenger = require('oroui/js/messenger'),
        routing = require('routing'),
        BasicTreeComponent = require('orob2bcatalog/js/app/components/basic-tree-component');

    /**
     * @export orob2bcms/js/app/components/tree-manage-component
     * @extends orob2bcatalog.app.components.BasicTreeComponent
     * @class orob2bcms.app.components.TreeManageComponent
     */
    TreeManageComponent = BasicTreeComponent.extend({
        /**
         * @property {Boolean}
         */
        updateAllowed : false,

        /**
         * @property {Boolean}
         */
        moveTriggered : false,

        /**
         * @property {String}
         */
        reloadWidget : '',

        /**
         * @param {Object} options
         */
        initialize: function (options) {
            TreeManageComponent.__super__.initialize.call(this, options);
            if (!this.$tree) {
                return;
            }

            this.updateAllowed = options.updateAllowed;
            this.reloadWidget = options.reloadWidget;

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
            if (options.updateAllowed) {
                config.plugins.push('dnd');
                config['dnd'] = {
                    'copy' : false
                };
            }

            return config;
        },

        /**
         * Triggers after page selection in tree
         *
         * @param {Object} node
         * @param {Object} selected
         */
        onSelect: function(node, selected) {
            if (this.initialization || !this.updateAllowed) {
                return;
            }

            var url = routing.generate('orob2b_cms_page_view', {id: selected.node.id});
            mediator.execute('redirectTo', {url: url});
        },

        /**
         * Triggers after page move
         *
         * @param {Object} e
         * @param {Object} data
         */
        onMove: function(e, data) {
            if (this.moveTriggered) {
                return;
            }

            var self = this;
            $.ajax({
                async: false,
                type: 'PUT',
                url: routing.generate('orob2b_cms_page_move'),
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
                            __("orob2b.cms.move_page_error", {nodeText: data.node.text})
                        );
                    } else if (self.reloadWidget) {
                        widgetManager.getWidgetInstanceByAlias(self.reloadWidget, function(widget) {
                            widget.render();
                        })
                    }
                }
            });
        },

        /**
         * Rollback page move
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
            var pageTree = this.$tree.parent();
            if (!pageTree.hasClass('page-tree')) {
                return;
            }

            var pageContainer = pageTree.parent();
            if (!pageContainer.hasClass('cms-page-container')) {
                return;
            }

            var fixHeight = function() {
                var anchor = $('#bottom-anchor').position().top;
                var container = pageContainer.position().top;
                var debugBarHeight = $('.sf-toolbar:visible').height() || 0;
                var footerHeight = $('#footer:visible').height() || 0;
                var fixContent = 1;

                pageTree.height(anchor - container - debugBarHeight - footerHeight + fixContent);
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
