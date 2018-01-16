define(function(require) {
    'use strict';

    var BackendGrid;
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var Grid = require('orodatagrid/js/datagrid/grid');
    var BackendToolbar = require('oroproduct/js/app/datagrid/backend-toolbar');
    var BackendSelectAllHeaderCell = require('oroproduct/js/app/datagrid/header-cell/backend-select-all-header-cell');
    var BackendActionHeaderCell = require('oroproduct/js/app/datagrid/header-cell/backend-action-header-cell');
    var SelectState = require('oroproduct/js/app/datagrid/products-select-state-model');

    var config = require('module').config();
    config = _.extend({
        massActionsInSticky: _.isMobile(),
        massActionsContainer: $('[data-mass-actions-container]'),
        massActionsStickyContainer: $('[data-mass-actions-sticky-container]')
    }, config);

    BackendGrid = Grid.extend({
        /** @property */
        toolbar: BackendToolbar,

        /** @property */
        themeOptions: {
            optionPrefix: 'backendgrid'
        },

        /** @property */
        massActionsContainer: config.massActionsContainer,

        /** @property */
        massActionsStickyContainer: config.massActionsStickyContainer,

        /** @property */
        visibleState: {
            visible: null
        },

        /** @property */
        massActionsInSticky: config.massActionsInSticky,

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

            mediator.on('widget:notFound', function() {
                $(window).off('beforeunload');
            });

            this._listenToDocumentEvents();
        },

        _listenToDocumentEvents: function() {
            $(window).on('beforeunload.' + this.cid, _.bind(this.onWindowUnload, this));
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

            this._afterRequest(params);
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
                'backgrid:getSelected': this.getSelected,
                'backgrid:setVisibleState': this.setVisibleState,
                'backgrid:getVisibleState': this.getVisibleState,
                'backgrid:checkUnSavedData': this.checkUnSavedData,
                'backgrid:hasMassActions': this.hasMassActions
            });
            this.listenTo(this.selectState, 'change', _.bind(_.debounce(this.showStickyContainer, 50), this));
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
                this.renderActionsArea();
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
        _afterRequest: function(jqXHR) {
            if (this.requestsCount === 1) {
                this.initLayout({collection: this.collection});
            }
            BackendGrid.__super__._afterRequest.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            this.collection.off('gridContentUpdate');
            mediator.off('grid-content-loaded');

            BackendGrid.__super__.undelegateEvents.apply(this, arguments);
        },

        renderActionsArea: function() {
            // Don't render Actions without data
            if (!this.massActions.length) {
                return ;
            }

            this.selectAllHeaderCell = new BackendSelectAllHeaderCell({
                collection: this.collection,
                selectState: this.selectState
            });

            this.selectHeaderActionCell = new BackendActionHeaderCell({
                collection: this.collection,
                column: this.columns.findWhere('massActions'),
                selectState: this.selectState,
                massActionsInSticky: this.massActionsInSticky
            });

            this.massActionsContainer.append(this.selectAllHeaderCell.$el);
            if (this.massActionsInSticky) {
                this.additionalSelectAllHeaderCell =  new BackendSelectAllHeaderCell({
                    collection: this.collection,
                    selectState: this.selectState,
                    additionalTpl: true
                });

                this.massActionsStickyContainer.append(this.additionalSelectAllHeaderCell.$el);
                this.massActionsStickyContainer.append(this.selectHeaderActionCell.$el);
            } else {
                this.massActionsContainer.append(this.selectHeaderActionCell.$el);
            }
        },

        showStickyContainer: function(selectState) {
            this.massActionsStickyContainer[selectState.isEmpty() ? 'addClass' : 'removeClass']('hidden');
        },

        setVisibleState: function(state) {
            this.visibleState.visible = state;
        },

        /**
         * @param {Object} obj
         */
        getVisibleState: function(obj) {
            if ($.isEmptyObject(obj) && _.isBoolean(this.visibleState.visible)) {
                obj.visible = this.visibleState.visible;
            }
        },

        /**
         * @param {Object} obj
         */
        hasMassActions: function(obj) {
            if ($.isEmptyObject(obj)) {
                obj.hasMassActions = !!this.massActions.length;
            }
        },

        onWindowUnload: function() {
            if (!this.selectState.isEmpty()) {
                return __('oro.ui.leave_page_with_unsaved_data_confirm');
            }
        },

        checkUnSavedData: function(obj) {
            var live = true;
            var self = this;

            if (!this.selectState.isEmpty()) {
                var confirm = function() {
                    var answer = window.confirm(__('oro.ui.leave_page_with_unsaved_data_confirm'));
                    if (answer) {
                        // Clear Selected State
                        self.collection.trigger('backgrid:selectNone');
                    }
                    return answer;
                };

                live = confirm();
            }

            if ($.isEmptyObject(obj)) {
                obj.live = live;
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            BackendGrid.__super__.dispose.apply(this, arguments);
            $(window).off('.' + this.cid);
        }
    });

    return BackendGrid;
});
