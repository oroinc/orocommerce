import FilterOptionsStateExtensions from 'orofrontend/js/app/datafilter/filter-options-state-extensions';

const CollapsedFilters = FilterOptionsStateExtensions.extend({
    /**
     * @inheritdoc
     */
    constructor: function CollapsedFilters(options) {
        CollapsedFilters.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        if (!options.datagrid) {
            throw new TypeError('The "datagrid" option is required.');
        }

        this.datagrid = options.datagrid;

        CollapsedFilters.__super__.initialize.call(this, options);
    },

    onceFilterManagerConnected() {
        this.saveState(this.datagrid.filterManager);
    },

    transformToCollapse(filterManager) {
        if (filterManager.renderMode === 'collapse-mode') {
            return;
        }
        for (const filter of Object.values(filterManager.filters)) {
            // stop any animations
            filter.$(filter.criteriaSelector).clearQueue().finish();
            filter.outerHintContainer = null;
            filter.autoClose = true;
            filter.animationDuration = 0;
            filter.initiallyOpened = false;
            filter._hideCriteria();
        }

        filterManager.$el.remove();
        filterManager.renderMode = 'collapse-mode';
        filterManager.enableMultiselectWidget = false;
        filterManager.filterContainer = this.datagrid
            .$('[data-grid-toolbar="top"] [data-role="filter-container"]')[0];
        filterManager.autoClose = true;
        filterManager.outerHintContainer = null;

        filterManager.trigger('filters-render-mode-changed', {
            renderMode: filterManager.renderMode,
            isAsInitial: false
        });
        filterManager.render();
        filterManager.$('[data-collapse-trigger]').on('collapse:toggle', (e, {isOpen}) => {
            if (isOpen) {
                filterManager.trigger('filters-render-mode-changed', {
                    renderMode: filterManager.renderMode,
                    isAsInitial: false
                });
                filterManager.$('[data-collapse-trigger]').off('collapse:toggle');
            }
        });
        filterManager.$el.addClass('collapsed');
    },

    transformToOriginal(filterManager) {
        if (filterManager.renderMode !== 'collapse-mode') {
            return;
        }
        filterManager.$('[data-collapse-trigger]').off('collapse:toggle');
        filterManager.$el.remove();
        this.restoreState(filterManager);
        filterManager.render();
        filterManager.trigger('filters-render-mode-changed', {
            renderMode: filterManager.renderMode,
            isAsInitial: true
        });
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        delete this.datagrid;
        return CollapsedFilters.__super__.dispose.call(this);
    }
});

export default CollapsedFilters;
