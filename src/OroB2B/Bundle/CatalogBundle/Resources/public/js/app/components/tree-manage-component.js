define(function (require) {
    'use strict';

    var TreeManageComponent,
        $ = require('jquery'),
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component');

    require('orob2bcatalog/js/lib/jstree/jstree');

    TreeManageComponent = BaseComponent.extend({
        initialize: function (options) {
            var tree = $(options._sourceElement);

            tree.jstree(
                {
                    'core' : {
                        'data' : {
                            'url' : Routing.generate('orob2b_category_list', { _format: 'json' })
                        },
                        'themes': {
                            'name': 'b2b'
                        }
                    },
                    'state' : {
                        'key' : 'b2b-category',
                        'filter': function(state) {
                            state.core.selected = options.categoryId ? [options.categoryId] : [];
                            return state;
                        }
                    },
                    'plugins': ['state']
                }
            );

            tree.on('select_node.jstree', function(node, selected) {
                var id = selected.node.id;
                if (id != options.categoryId) {
                    var url = Routing.generate('orob2b_catalog_category_update', { id : id });
                    mediator.execute('redirectTo', {url: url});
                }
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }

            TreeManageComponent.__super__.dispose.call(this);
        }
    });

    return TreeManageComponent;
});
