define(function (require) {
    'use strict';

    var TreeManageComponent,
        $ = require('jquery'),
        BaseComponent = require('oroui/js/app/components/base/component');

    require('orob2bcatalog/js/lib/jstree/jstree');

    TreeManageComponent = BaseComponent.extend({
        initialize: function (options) {
            var $tree = $(options._sourceElement),
                categoryList = options.data;

            $tree.jstree(
                {
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
                }
            )
        }
    });

    return TreeManageComponent;
});