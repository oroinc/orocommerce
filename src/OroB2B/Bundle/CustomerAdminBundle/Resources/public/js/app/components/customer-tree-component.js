define(function (require) {
    'use strict';

    var CustomerTreeComponent,
        $ = require('jquery'),
        BaseComponent = require('oroui/js/app/components/base/component');

    require('jquery.jstree');

    /**
     * Options:
     * - data - tree structure in jstree json format
     *
     * @export orob2bcustomeradmin/js/app/components/basic-tree-component
     * @extends oroui.app.components.base.Component
     * @class orob2bcustomeradmin.app.components.CustomerTreeComponent
     */
    CustomerTreeComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        $tree : null,

        /**
         * @param {Object} options
         */
        initialize: function (options) {
            var nodeList = options.data;
            if (!nodeList) {
                return;
            }

            this.$tree = $(options._sourceElement);

            var config = {
                'core' : {
                    'multiple' : false,
                    'data' : nodeList,
                    'check_callback' : true,
                    'themes': { 'name': 'b2b'}
                },
                'state' : {'key' : options.key},
                'plugins': ['state']
            };

            this._deferredInit();

            this.$tree.jstree(config);

            var self = this;
            this.$tree.on('ready.jstree', function () {
                self._resolveDeferredInit();
            });
        }
    });

    return CustomerTreeComponent;
});
