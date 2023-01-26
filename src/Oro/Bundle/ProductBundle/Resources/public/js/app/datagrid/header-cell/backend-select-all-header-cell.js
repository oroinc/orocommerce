define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const template = require('tpl-loader!oroproduct/templates/datagrid/backend-select-all-header-cell.html');
    const SelectAllHeaderCell = require('orodatagrid/js/datagrid/header-cell/select-all-header-cell');
    const viewportManager = require('oroui/js/viewport-manager').default;

    const modes = {
        DROPDOWN: 'Dropdown',
        SIMPLE: 'Simple'
    };

    const BackendSelectAllHeaderCell = SelectAllHeaderCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
        className: 'product-action',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        listen() {
            return {
                [`viewport:${this.optimizedScreenSize} mediator`]: 'defineRenderingStrategy'
            };
        },

        /**
         * @inheritdoc
         */
        constructor: function BackendSelectAllHeaderCell(options) {
            BackendSelectAllHeaderCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!options.optimizedScreenSize) {
                throw new Error('The "optimizedScreenSize" option is required.');
            }

            this.optimizedScreenSize = options.optimizedScreenSize;
            this.collection = options.collection;
            this.selectState = options.selectState;
            this.defineRenderingStrategy();
        },

        /**
         * @inheritdoc
         */
        delegateListeners: function() {
            this.listenTo(this.selectState, 'undo-selection', this.onSelectUnbind.bind(this));
            this.listenTo(this, 'render-mode:changed', state => this.render());

            return BackendSelectAllHeaderCell.__super__.delegateListeners.call(this);
        },


        /**
         * @inheritdoc
         */
        delegateEvents: function(events) {
            this.$('[data-checkbox-change-visible]')
                .on('change' + this.eventNamespace(), _.debounce(this.onCheckboxChange.bind(this), 50));
            this.collection.on('backgrid:visible-changed', _.debounce(this.unCheckCheckbox.bind(this), 50));
            this.listenTo(this.selectState, 'change', _.debounce(this.updateState.bind(this), 50));

            return BackendSelectAllHeaderCell.__super__.delegateEvents.call(this, events);
        },

        defineRenderingStrategy() {
            const prevRenderMode = this.renderMode;

            if (this._isSimple()) {
                this.renderMode = modes.SIMPLE;
            } else {
                this.renderMode = modes.DROPDOWN;
            }

            if (prevRenderMode !== this.renderMode) {
                this.trigger('render-mode:changed', {
                    prevRenderMode,
                    renderMode: this.renderMode
                });
            }
        },

        onCheckboxClick: function(e) {
            if (this.selectState.get('inset') && this.selectState.isEmpty()) {
                this.collection.trigger('backgrid:selectAllVisible');
            } else {
                this.collection.trigger('backgrid:selectNone');
            }
            e.stopPropagation();
        },

        onCheckboxChange: function(event) {
            this.updateVisibleState($(event.currentTarget).is(':checked'));
        },

        updateVisibleState(state = true) {
            if (!state) {
                this.collection.trigger('backgrid:selectNone');
            }

            this.collection.trigger('backgrid:selectNone');
            this.collection.trigger('backgrid:setVisibleState', state);

            this.canSelect(state);

            this.$('[data-checkbox-change-visible]')
                .prop('checked', state)
                .parent()
                .toggleClass('checked', state);
        },

        onSelectUnbind: function() {
            this.collection.trigger('backgrid:selectNone');
            this.collection.trigger('backgrid:visible-changed');
            this.canSelect(false);
            this.collection.trigger('backgrid:setVisibleState', false);
        },

        canSelect: function(flag) {
            this.collection.each(function(model) {
                model.trigger('backgrid:canSelected', flag);
            });
        },

        unCheckCheckbox: function() {
            this.$('[data-checkbox-change-visible]')
                .prop('checked', false)
                .parent()
                .removeClass('checked');
        },

        _isSimple() {
            return viewportManager.isApplicable(this.optimizedScreenSize);
        },

        /**
         * @inheritdoc
         */
        getTemplateData() {
            const data = BackendSelectAllHeaderCell.__super__.getTemplateData.call(this);

            data.isSimple = this._isSimple();

            return data;
        },

        render() {
            BackendSelectAllHeaderCell.__super__.render.call(this);

            this.$el.trigger('content:changed');

            if (this.renderMode === modes.SIMPLE) {
                this.updateVisibleState(false);
            } else {
                this.updateVisibleState();
            }

            return this;
        }
    });

    return BackendSelectAllHeaderCell;
});
