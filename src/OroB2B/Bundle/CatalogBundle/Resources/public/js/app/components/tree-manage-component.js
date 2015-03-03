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
                categoryList = options.data;

            if (!categoryList) {
                return;
            }

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
                    'filter' : function(state) {
                        state.core.selected = options.categoryId ? [options.categoryId] : [];
                        return state;
                    }
                },
                'plugins' : ['state']
            });

            $tree.on('select_node.jstree', function(node, selected) {
                var id = selected.node.id;
                if (id != options.categoryId) {
                    var url = Routing.generate('orob2b_catalog_category_update', { id : id });
                    mediator.execute('redirectTo', {url: url});
                }
            });
        }
    });

    return TreeManageComponent;
});
