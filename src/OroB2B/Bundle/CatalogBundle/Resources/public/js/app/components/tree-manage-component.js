define(function (require) {
    'use strict';

    var TreeManageComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        mediator = require('oroui/js/mediator'),
        messenger = require('oroui/js/messenger'),
        __ = require('orotranslation/js/translator'),
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
            this.$tree = $(options._sourceElement);
            var categoryList = options.data,
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
                };
            }

            this.$tree.jstree(config);

            this.$tree.on('select_node.jstree', _.bind(this.onSelect, this));
            this.$tree.on('move_node.jstree', _.bind(this.onMove, this));

            var self = this;
            this.$tree.on('ready.jstree', function () {
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

            if (data.parent == '#') {
                this.rollback(data);
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
                    if (!result.status) {
                        self.rollback(data);
                    }
                }
            });
        },

        /**
         * Rollback category move
         *
         * @param {Object} data
         */
        rollback: function(data)
        {
            this.moveTriggered = true;
            this.$tree.jstree('move_node', data.node, data.old_parent, data.old_position);
            this.moveTriggered = false;
            var placeholders = {nodeText: data.node.text};
            messenger.notificationFlashMessage('error', __("orob2b.catalog.moveCategoryError", placeholders));

        }
    });

    return TreeManageComponent;
});
