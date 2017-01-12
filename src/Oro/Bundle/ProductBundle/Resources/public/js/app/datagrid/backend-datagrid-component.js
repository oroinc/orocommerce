define(function(require) {
    'use strict';

    var ProductDataGidComponent;
    var DataGridComponent = require('orodatagrid/js/app/components/datagrid-component');
    var mapCustomModuleName = require('oroproduct/js/app/datagrid/map-custom-module-name');

    ProductDataGidComponent = DataGridComponent.extend({
        /**
         * @inheritDoc
         */
        collectModules: function() {
            // If another Grid view is present
            if (this.metadata.GridView) {
                // Load custom Grid
                this.modules.GridView = mapCustomModuleName(this.metadata.GridView);
            }
            // If another PageableCollection is present
            if (this.metadata.PageableCollection) {
                // Load custom PageableCollection
                this.modules.PageableCollection = mapCustomModuleName(this.metadata.PageableCollection);
            }

            ProductDataGidComponent.__super__.collectModules.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        insertDataGrid: function(options) {
            var selector = options.gridMainContainer || '.oro-datagrid';

            this.$el = options.$el.find(selector);
        },

        /**
         * @inheritDoc
         */
        build: function() {
            ProductDataGidComponent.__super__.build.apply(this, arguments);

            this.grid.on('shown', function() {
                this.$el.removeAttr('data-skip-input-widgets').inputWidget('seekAndCreate');
            }, this);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            this.grid.off('shown');

            ProductDataGidComponent.__super__.dispose.call(this);
        }
    });

    return ProductDataGidComponent;
});
