define(function (require) {
    'use strict';

    var TreeManageComponent,
        $ = require('jquery'),
        _ = require('underscore'),
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
         * @param {Object} options
         */
        initialize: function (options) {
            TreeManageComponent.__super__.initialize.call(this, options);
            if (!this.$tree) {
                return;
            }

            this.updateAllowed = options.updateAllowed;

            this.$tree.on('select_node.jstree', _.bind(this.onSelect, this));

            this._fixContainerHeight();
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
            if (!pageContainer.hasClass('page-container')) {
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
