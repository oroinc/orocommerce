define(function (require) {
    'use strict';

    var TreeManageComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component');

    require('jquery.jstree');

    /**
     * Options:
     * - data - tree structure in jstree json format
     * - categoryId - identifier of selected category
     */
    TreeManageComponent = BaseComponent.extend({
        /**
         * @property {Number}
         */
        categoryId : null,

        /**
         * @property {Boolean}
         */
        initialization : false,

        /**
         * @param {Object} options
         */
        initialize: function (options) {
            var $tree = $(options._sourceElement),
                categoryList = options.data;

            if (!categoryList) {
                return;
            }

            this.categoryId = options.categoryId;

            this._deferredInit();
            this.initialization = true;

            $tree.jstree({
                'core' : {
                    'multiple' : false,
                    'data' : categoryList,
                    'themes': {
                        'name': 'b2b'
                    }
                },
                'state' : {
                    'key' : 'b2b-category',
                    'filter' : _.bind(this.onFilter, this)
                },
                'plugins' : ['state']
            });

            $tree.on('select_node.jstree', _.bind(this.onSelect, this));

            var self = this;
            $tree.on('ready.jstree', function () {
                self._resolveDeferredInit();
                self.initialization = false;
            });
        },

        /**
         * Filters tree state
         *
         * @param {Object} state
         * @returns {Object}
         */
        onFilter: function(state) {
            state.core.selected = this.categoryId ? [this.categoryId] : [];
            return state;
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
        }
    });

    return TreeManageComponent;
});
