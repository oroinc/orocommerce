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
     * - categoryId - identifier of selected category
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
        categoryId : null,

        /**
         * @property {Boolean}
         */
        initialization : false,

        /**
         * @param {Object} options
         */
        initialize: function (options) {
            var categoryList = options.data;
            if (!categoryList) {
                return;
            }

            this.$tree = $(options._sourceElement);

            var config = {
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
            config = this.customizeTreeConfig(options, config);

            this.categoryId = options.categoryId;

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
            state.core.selected = this.categoryId ? [this.categoryId] : [];
            return state;
        }
    });

    return BasicTreeComponent;
});
