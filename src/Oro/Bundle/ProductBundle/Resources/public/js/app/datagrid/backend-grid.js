define(function(require) {
    'use strict';

    var BackendGrid;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var Grid = require('orodatagrid/js/datagrid/grid');
    var BackendToolbar = require('oroproduct/js/app/datagrid/backend-toolbar');

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
        initialize: function (options) {
            this.header = null;
            this.footer = null;
            this.body = null;

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
            this.themeOptions.serverRendered = true;

            this.$el.find('.grid-body').html(params.gridContent.html());

            this.collection.reset(params.responseJSON.data.data);
            this.initLayout({collection: this.collection});
            this._afterRequest(params.responseJSON);
        },

        /**
         * @inheritDoc
         */
        backgridInitialize: function() {},

        /**
         * @inheritDoc
         */
        render: function() {
            this.$grid = this.$(this.selectors.grid);

            this.renderToolbar();
            this.renderNoDataBlock();
            this.renderLoadingMask();

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

            BackendGrid.__super__.undelegateEvents.apply(this, arguments);
        }
    });

    return BackendGrid;
});
