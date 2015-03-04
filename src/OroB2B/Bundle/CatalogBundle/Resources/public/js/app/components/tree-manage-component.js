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
        moveTriggered : false,

        /**
         * @property {Boolean}
         */
        initialization : false,

        /**
         * @param {Object} options
         */
        initialize: function (options) {
            var $tree = $(options._sourceElement),
                categoryList = options.data,
                config = {
                    'core' : {
                        'multiple' : false,
                        'data' : categoryList,
                        'check_callback' : true,
                        'themes': {
                            'name': 'b2b'
                        }
                    },
                    'state' : {
                        'key' : 'b2b-category',
                        'filter' : _.bind(this.onFilter, this)
                    },

                    'plugins': ['state']
                };

            if (!categoryList) {
                return;
            }

            this.categoryId = options.categoryId;

            this._deferredInit();
            this.initialization = true;

            if (options.dndEnabled) {
                config.plugins.push('dnd');
                config['dnd'] = {
                    'copy' : false
                }
            }

            $tree.jstree(config);

            $tree.on('select_node.jstree', _.bind(this.onSelect, this));
            $tree.on('move_node.jstree', _.bind(this.onMove, this));

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

            var self = this;
            $.ajax({
                async: false,
                type: 'PUT',
                url: Routing.generate('orob2b_category_move'),
                data: {
                    id: data.node.id,
                    parent: data.parent,
                    position: data.position
                },
                success: function (result) {
                    if (!result.status.moved) {
                        self.moveTriggered = true;
                        $tree.jstree('move_node', data.node, data.old_parent, data.old_position);
                        self.moveTriggered = false;
                        throw new Error('Can not move node ' + data.node.id + '.' + result.status.error);
                    }
                }
            });
        }
    });

    return TreeManageComponent;
});
