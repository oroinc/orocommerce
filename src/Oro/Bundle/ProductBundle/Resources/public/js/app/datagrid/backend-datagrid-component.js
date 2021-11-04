define(function(require) {
    'use strict';

    const DataGridComponent = require('orodatagrid/js/app/components/datagrid-component');
    const mapCustomModuleName = require('oroproduct/js/app/datagrid/map-custom-module-name');

    const ProductDataGidComponent = DataGridComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function ProductDataGidComponent(options) {
            ProductDataGidComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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

            ProductDataGidComponent.__super__.collectModules.call(this);
        },

        /**
         * @inheritdoc
         */
        insertDataGrid: function(options) {
            const selector = options.gridMainContainer || '.oro-datagrid';

            this.$el = options.$el.find(selector);
        },

        /**
         * @inheritdoc
         */
        build: function(...args) {
            ProductDataGidComponent.__super__.build.apply(this, args);

            this.grid.on('shown', function() {
                this.$el.removeAttr('data-skip-input-widgets').inputWidget('seekAndCreate');
            }, this);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            this.grid.off(null, null, this);

            ProductDataGidComponent.__super__.dispose.call(this);
        }
    });

    return ProductDataGidComponent;
});
