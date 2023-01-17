import BaseComponent from 'oroui/js/app/components/base/component';
import $ from 'jquery';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import ApplyFilterView from 'oroproduct/js/app/views/sidebar-filters/filter-applier-view';

const FilterApplierComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function FilterApplierComponent(options) {
        FilterApplierComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.filterManager = options.filterManager;
        this._changedFiltersState = {};

        this.renderButton();
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        delete this._changedFiltersState;
        delete this.filterManager;
        return FilterApplierComponent.__super__.dispose.call(this);
    },

    renderButton() {
        this.button = new ApplyFilterView({
            autoRender: true
        });
    },

    /**
     * @inheritdoc
     */
    delegateListeners: function() {
        FilterApplierComponent.__super__.delegateListeners.call(this);

        for (const filter of Object.values(this.filterManager.filters)) {
            this.listenTo(filter, {
                change: this.onFilterChanged.bind(this, filter),
                update: this.onFilterUpdate.bind(this, filter),
                reset: this.onFilterReset.bind(this, filter),
                hideCriteria: this.onFilterHidden.bind(this, filter),
                showCriteria: this.onFilterShown.bind(this, filter)
            });
        }
        this.listenTo(this.filterManager, {
            'update-view:before-fetch': () => {
                if (!this._wasResetAction) {
                    this.applyChangedState();
                    delete this._wasResetAction;
                }
            }
        });
        this.listenTo(this.filterManager.collection, 'beforeFetch', this.onBeforeFetch.bind(this));
        this.listenTo(this.button, 'apply-changes', this.applyAllChangedFilters);
        this.listenTo(mediator, 'grid_load:complete', collection => {
            if (collection.inputName === this.filterManager.collection.inputName) {
                this.setUnAppliedFilers();
            }
        });

        return this;
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    onFilterShown(filter) {
        const promise = filter.$el.find(filter.criteriaSelector).is(':animated')
            ? filter.$el.find(filter.criteriaSelector).promise()
            : $.Deferred().resolve();

        // Update button after animation is finished or immediately
        promise.always(() => {
            if (
                this.lastChanged === filter.cid &&
                filter.$el.hasClass(filter.buttonActiveClass) &&
                this.isFilterValueChanged(filter)
            ) {
                this.showButton(filter);
            } else {
                this.button.updatePosition();
            }
        });
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    onFilterHidden(filter) {
        const promise = filter.$el.find(filter.criteriaSelector).is(':animated')
            ? filter.$el.find(filter.criteriaSelector).promise()
            : $.Deferred().resolve();

        // Hide button immediately
        if (this.lastChanged === filter.cid) {
            this.button.unstick();
        } else {
            // Update button position after animation is finished
            promise.always(() => this.button.updatePosition());
        }
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    onFilterChanged(filter) {
        if (this._wasResetAction) {
            delete this._wasResetAction;
        }

        if (this.isFilterValueChanged(filter)) {
            if (this.lastChanged !== filter.cid) {
                this.previousChanged = this.lastChanged;
            }
            this.lastChanged = filter.cid;
            this._changedFiltersState[filter.name] = this.getRawFilterValue(filter);
            this.showButton(filter);
        } else if (this.lastChanged === filter.cid) {
            this.button.unstick();
        }
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    onFilterUpdate(filter) {
        if (
            this._changedFiltersState[filter.name] !== void 0 &&
            _.isEqual(this._changedFiltersState[filter.name], filter.getValue())
        ) {
            delete this._changedFiltersState[filter.name];
        }
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    onFilterReset(filter) {
        this._wasResetAction = true;
        if (
            this._changedFiltersState[filter.name] !== void 0 &&
            _.isEqual(this._changedFiltersState[filter.name], filter.getValue())
        ) {
            delete this._changedFiltersState[filter.name];
        }
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    isFilterValueChanged(filter) {
        const value = filter.getValue();
        const rawValue = this.getRawFilterValue(filter);
        const isValid = _.isFunction(filter._isValid) ? filter._isValid() : true;

        return filter.renderable &&
            isValid &&
            filter._isDOMValueChanged() &&
            filter.isUpdatable(rawValue, filter.value) &&
            !_.isEqual(value, rawValue);
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     * @return {*}
     */
    getRawFilterValue(filter) {
        const data = filter._formatRawValue(filter._readDOMValue());

        if (_.isFunction(filter.swapValues)) {
            return filter.swapValues(data);
        }

        return data;
    },

    onBeforeFetch() {
        this.button.unstick();
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     * {HTMLElement|null}
     */
    getReferenceElement(filter) {
        const $criteriaEl = filter.$(filter.criteriaSelector);

        if (!$criteriaEl.length) {
            return null;
        }

        const {value_end: valueEnd, value} = filter.criteriaValueSelectors || {};
        const $valueEndEl = $criteriaEl.find(valueEnd);
        const $valueEl = $criteriaEl.find(value);

        if ($valueEndEl.is(':visible')) {
            return $valueEndEl[0];
        } else if ($valueEl.is(':visible')) {
            return $valueEl[0];
        }

        return $criteriaEl[0];
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    showButton(filter) {
        // Do not show button if filter has checkboxes or disposed or disabled
        if (filter.selectWidget || filter.disposed || !filter.renderable) {
            return;
        }

        const referenceEl = this.getReferenceElement(filter);

        if (!referenceEl) {
            return;
        }

        if (!$.contains(filter.el, this.button.el)) {
            this.button.$el.appendTo(filter.$el);
        }

        this.button.stick(referenceEl);
    },

    /**
     * @returns {object}
     */
    getAppliedSate() {
        return {...this.filterManager.collection.state.filters, ...this._changedFiltersState};
    },

    /**
     * Set changed filters state into collection
     */
    applyChangedState() {
        if (Object.values(this._changedFiltersState).length) {
            this.filterManager._applyState({
                ...this.filterManager.collection.state.filters,
                ...this._changedFiltersState
            });
        }
    },

    applyAllChangedFilters() {
        if (this._wasResetAction) {
            delete this._wasResetAction;
        }
        this.applyChangedState();
    },

    setUnAppliedFilers() {
        let hasChanges = false;
        const filtersState = this.filterManager.collection.state.filters;

        for (const [name, value] of Object.entries(this._changedFiltersState)) {
            if (filtersState[name] === void 0 || !_.isEqual(value, filtersState[name])) {
                hasChanges = true;
                break;
            }
        }

        if (hasChanges) {
            let relatedFilter;

            for (const filterName in this.filterManager.filters) {
                if (this.filterManager.filters.hasOwnProperty(filterName)) {
                    const filter = this.filterManager.filters[filterName];
                    const isValid = _.isFunction(filter._isValid) ? filter._isValid() : true;

                    if (
                        filter.cid === this.lastChanged &&
                        this._changedFiltersState[filter.name] &&
                        isValid
                    ) {
                        relatedFilter = filter;
                        break;
                    } else if (
                        filter.cid === this.previousChanged &&
                        this._changedFiltersState[filter.name] &&
                        isValid
                    ) {
                        relatedFilter = filter;
                        break;
                    }
                }
            }

            if (relatedFilter) {
                this.showButton(relatedFilter);
                const appliedSate = this.getAppliedSate();

                _.each(this.filterManager.filters, filter => {
                    if (appliedSate[filter.name] && filter.renderable) {
                        filter._writeDOMValue(appliedSate[filter.name]);
                    }
                });
            }
        }
    }
});

export default FilterApplierComponent;
