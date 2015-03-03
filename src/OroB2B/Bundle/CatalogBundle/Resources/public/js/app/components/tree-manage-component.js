define(function (require) {
    'use strict';

    var TreeManageComponent,
        $ = require('jquery'),
        BaseComponent = require('oroui/js/app/components/base/component');

    require('orob2bcatalog/js/lib/jstree/jstree');

    /**
     * Options:
     * - data - tree structure in jstree json format
     * - categoryId - identifier of selected category
     */
    TreeManageComponent = BaseComponent.extend({
        initialize: function (options) {
            var $tree = $(options._sourceElement),
                categoryList = options.data,
                moveTriggered = false,
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
                        'filter' : function(state) {
                            state.core.selected = options.categoryId ? [options.categoryId] : [];
                            return state;
                        }
                    },

                    'plugins': ['state']
                };

            if (options.dnd_enable) {
                config.plugins.push('dnd');
                config['dnd'] = {
                    'copy' : false
                }
            }

            $tree.jstree(config);
            
            $tree.on('move_node.jstree', function (e, data) {
                if (moveTriggered) {
                    return;
                }
                
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
                            moveTriggered = true;
                            $tree.jstree('move_node', data.node, data.old_parent, data.old_position);
                            moveTriggered = false;
                            throw new Error('Can not move node ' + data.node.id + '.' + result.status.error);
                        }
                    }
                });
            })
        }
    });

    return TreeManageComponent;
});
