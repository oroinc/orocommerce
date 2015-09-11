define(function (require) {
    'use strict';

    var BasicTreeComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component');

    require('jquery.jstree');

    /**
     * Options:
     * - data - tree structure in jstree json format
     * - nodeId - identifier of selected node
     *
     * @export orob2bcatalog/js/app/components/basic-tree-component
     * @extends oroui.app.components.base.Component
     * @class orob2bcatalog.app.components.BasicTreeComponent
     */
    BasicTreeComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        $tree : null,

        /**
         * @property {Number}
         */
        nodeId : null,

        /**
         * @property {Boolean}
         */
        initialization : false,

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
                    'themes': {
                        'name': 'b2b'
                    }
                },
                'state' : {
                    'key' : options.key,
                    'filter' : _.bind(this.onFilter, this)
                },

                'plugins': ['state']
            };
            config = this.customizeTreeConfig(options, config);

            this.nodeId = options.nodeId;

            this._deferredInit();
            this.initialization = true;

            this.$tree.jstree(config);

            var self = this;
            this.$tree.on('ready.jstree', function () {
                self._resolveDeferredInit();
                self.initialization = false;
            });
        },

        /**
         * Customize jstree config to add plugins, callbacks etc.
         *
         * @param {Object} options
         * @param {Object} config
         * @returns {Object}
         */
        customizeTreeConfig: function(options, config) {
            return config;
        },

        /**
         * Filters tree state
         *
         * @param {Object} state
         * @returns {Object}
         */
        onFilter: function(state) {
            state.core.selected = this.nodeId ? [this.nodeId] : [];
            return state;
        }
    });

    return BasicTreeComponent;
});
