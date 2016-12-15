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
                this.themeOptions.serverRendered = true;
                this.trigger('content:update');
                this.$el.find('.grid-body').html(params.content.html());

                this.collection.reset(params.collection);
                this.backgridRefresh();
             }, this);

            BackendGrid.__super__.initialize.apply(this, arguments);
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

            this.delegateEvents();
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

            this.switchAppearanceClass(_.result(this.metadata.state, 'appearanceType'));
            return this;
        }
    });

    return BackendGrid;
});
