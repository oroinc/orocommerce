define(function(require) {
    'use strict';

    var BackendGrid;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var Grid = require('orodatagrid/js/datagrid/grid');
    var BackendToolbar = require('oroproduct/js/app/datagrid/backend-toolbar');
    var BackendSelectAllHeaderCell = require('oroproduct/js/app/datagrid/header-cell/backend-select-all-header-cell');
    var BackendSelectHeaderCell = require('oroproduct/js/app/datagrid/header-cell/backend-action-header-cell');
    var SelectState = require('orodatagrid/js/datagrid/select-state-model');

    BackendGrid = Grid.extend({
        /** @property */
        toolbar: BackendToolbar,

        /** @property */
        themeOptions: {
            optionPrefix: 'backendgrid'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.header = null;
            this.footer = null;
            this.body = null;
            this.multiSelectRowEnabled = options.multiSelectRowEnabled;

            mediator.on('grid-content-loaded', function(params) {
                this.updateGridContent(params);
            }, this);

            BackendGrid.__super__.initialize.apply(this, arguments);
        },

        /**
         * Update grid content after load new content
         *
         * @param {object} params
         */
        updateGridContent: function(params) {
            this.$el.find('.grid-body').html(params.gridContent.html());

            mediator.trigger('datagrid_filters:update', this);
            this.collection.updateState(params.responseJSON.data.options);
            this.collection.reset(params.responseJSON.data.data);

            this.initLayout({collection: this.collection});
            this._afterRequest(params.responseJSON);
        },

        /**
         * @inheritDoc
         */
        backgridInitialize: function() {
            if (this.selectState === null) {
                this.selectState = new SelectState();
            }

            this.listenTo(this.collection, {
                'remove': this.onCollectionModelRemove,
                'updateState': this.onCollectionUpdateState,
                'backgrid:selected': this.onSelectRow,
                'backgrid:selectAll': this.selectAll,
                'backgrid:selectAllVisible': this.selectAllVisible,
                'backgrid:selectNone': this.selectNone,
                'backgrid:isSelected': this.isSelected,
                'backgrid:getSelected': this.getSelected
            });
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this.$grid = this.$(this.selectors.grid);

            this.renderToolbar();
            this.renderNoDataBlock();
            this.renderLoadingMask();
            if (this.multiSelectRowEnabled) {
                this.renderSelectAll();
            }

            this.listenTo(this.collection, 'reset', this.renderNoDataBlock);

            this._deferredRender();

            mediator.trigger('grid_load:complete', this.collection, this.$grid);

            this.initLayout({
                collection: this.collection
            }).always(_.bind(function() {
                this.rendered = true;
                /**
                 * Backbone event. Fired when the grid has been successfully rendered.
                 * @event rendered
                 */
                this.trigger('rendered');

                /**
                 * Backbone event. Fired when data for grid has been successfully rendered.
                 * @event grid_render:complete
                 */
                mediator.trigger('grid_render:complete', this.$el);
                this._resolveDeferredRender();
            }, this));

            this.rendered = true;
            var self = this;

            this.switchAppearanceClass(_.result(this.metadata.state, 'appearanceType'));

            this.collection.on('gridContentUpdate', function() {
                self._beforeRequest();
            });
            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            this.collection.off('gridContentUpdate');
            mediator.off('grid-content-loaded');

            BackendGrid.__super__.undelegateEvents.apply(this, arguments);
        },

        renderSelectAll: function() {
            new BackendSelectAllHeaderCell({
                collection: this.collection,
                selectState: this.selectState
            });

            new BackendSelectHeaderCell({
                collection: this.collection,
                column: this.columns.findWhere('massActions')
            });
        }
    });

    return BackendGrid;
});
