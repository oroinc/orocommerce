define(function (require) {
    'use strict';

    var TreeManageComponent,
        $ = require('jquery'),
        BaseComponent = require('oroui/js/app/components/base/component');

    require('orob2bcatalog/js/lib/jstree/jstree');

    TreeManageComponent = BaseComponent.extend({
        initialize: function (options) {
            this.$elem = options._sourceElement;

            $(this.$elem).jstree(
                {
                    'core' : {
                        'data' : {
                            'url' : Routing.generate(
                                'orob2b_category_list', { _format: 'json', selectedCategoryId: options.categoryId}
                            )
                        },
                        'themes': {
                            'name': 'b2b'
                            //'url': true,
                            //'dir': '/bundles/orob2bcatalog/css/lib/jstree/themes'
                        }
                    }
                }
            )
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