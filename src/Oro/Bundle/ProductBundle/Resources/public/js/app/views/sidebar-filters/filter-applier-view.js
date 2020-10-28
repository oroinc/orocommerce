import BaseView from 'oroui/js/app/views/base/view';
import applyFilterTemplate from 'tpl-loader!oroproduct/templates/sidebar-filters/filter-applier.html';
import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import Popper from 'popper';

const FilterApplierView = BaseView.extend({
    /**
     * @inheritDoc
     */
    template: applyFilterTemplate,

    /**
     * @inheritDoc
     */
    className: 'apply-filters',

    buttonOptions: {
        label: __('oro_frontend.filters.apply'),
        classes: 'btn btn--action btn--size-s'
    },

    /**
     * @inheritDoc
     */
    events: {
        'click [data-role="apply"]': 'onClick'
    },

    /**
     * @inheritDoc
     */
    optionNames: BaseView.prototype.optionNames.concat(['buttonOptions', 'filterManager']),

    /**
     * @inheritDoc
     */
    constructor: function FilterApplierView(options) {
        FilterApplierView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        this.filterManager = options.filterManager;
        this._changedFilters = {};
        this.filterManager._updateView = _.wrap(this.filterManager._updateView, (original, ...rest) => {
            _.reduce(this.filterManager.filters, (changedFilters, filter) => {
                if (this.isFilterValueChanged(filter)) {
                    changedFilters[filter.name] = this.getRawFilterValue(filter);
                }

                return changedFilters;
            }, this._changedFilters);

            if (Object.keys(this._changedFilters).length) {
                const state = {
                    ...this.filterManager.collection.state.filters,
                    ...this._changedFilters
                };

                this.filterManager.collection.updateState({filters: state});
            }

            return original.apply(this.filterManager, rest);
        });

        FilterApplierView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritDoc
     */
    delegateListeners: function() {
        FilterApplierView.__super__.delegateListeners.call(this);

        _.each(this.filterManager.filters, filter => {
            this.listenTo(filter, {
                change: this.onFilterChanged.bind(this, filter),
                hideCriteria: this.onFilterHidden.bind(this, filter),
                showCriteria: this.onFilterShown.bind(this, filter)
            });
        });

        this.listenTo(this.filterManager.collection, 'beforeFetch', this.onBeforeFetch);

        return this;
    },

    /**
     * @inheritDoc
     */
    render() {
        if (this.rendered) {
            return;
        }

        this.rendered = true;
        this.filterManager.$el.prepend(this.$el);

        return FilterApplierView.__super__.render.call(this);
    },

    /**
     * @inheritDoc
     */
    getTemplateData: function() {
        const data = FilterApplierView.__super__.getTemplateData.call(this);

        data.buttonOptions = this.buttonOptions;

        return data;
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    onFilterShown(filter) {
        if (this.lastChanged === filter.cid && this.isFilterValueChanged(filter)) {
            this.show(filter);
        } else {
            this.updatePopper();
        }
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    onFilterHidden(filter) {
        if (this.lastChanged === filter.cid) {
            this.hide();
        } else {
            this.updatePopper();
        }
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    onFilterChanged(filter) {
        if (this.isFilterValueChanged(filter)) {
            this.lastChanged = filter.cid;
            this._changedFilters[filter.name] = this.getRawFilterValue(filter);
            this.show(filter);
        } else if (this.lastChanged === filter.cid) {
            this.hide();
        }
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    isFilterValueChanged(filter) {
        const rawValue = this.getRawFilterValue(filter);
        const isValid = _.isFunction(filter._isValid) ? filter._isValid() : true;

        return filter.enabled &&
            isValid &&
            filter._isDOMValueChanged() &&
            filter.isUpdatable(rawValue, filter.value);
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
        this._changedFilters = {};
        if (this.$el.is(':visible')) {
            this.hide();
        }
    },

    /**
     * @inheritDoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        delete this._changedFilters;
        this.destroyPopper();

        FilterApplierView.__super__.dispose.call(this);
    },

    /**
     * @param {HTMLElement} referenceEl - The element used to position the popper
     * @param {HTMLElement} popperEl - The element used as the popper
     */
    initPopper(referenceEl, popperEl) {
        this.destroyPopper();

        this.popper = new Popper(referenceEl, popperEl, {
            placement: _.isRTL() ? 'left' : 'right',
            positionFixed: false,
            removeOnDestroy: false,
            modifiers: {
                flip: {
                    enabled: true,
                    fn(data, options) {
                        Popper.Defaults.modifiers.flip.fn(data, options);

                        if (data.flipped) {
                            data.placement = 'top';
                            Popper.Defaults.modifiers.flip.fn(data, options);
                        }

                        return data;
                    }
                },
                arrow: {
                    element: '.arrow'
                },
                preventOverflow: {
                    boundariesElement: 'window'
                }
            }
        });
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     */
    show(filter) {
        const popperReference = filter.$(filter.criteriaSelector)[0];

        if (filter.disposed || !filter.enabled || popperReference === void 0) {
            return;
        }

        if (!this.rendered) {
            this.render();
        }

        if (!$.contains(filter.el, this.el)) {
            this.$el.appendTo(filter.$el);
            this.initPopper(popperReference, this.el);
        }

        this.$el.removeClass('hide');
    },

    hide() {
        // Move a root element back to original position if it is present in a DOM
        if (this.rendered) {
            this.filterManager.$el.prepend(this.$el);
        }
        this.$el.addClass('hide');
        this.destroyPopper();
    },

    destroyPopper() {
        if (this.popper) {
            this.popper.destroy();
            this.popper = null;
        }
    },

    updatePopper() {
        if (!this.disposed && this.popper) {
            this.popper.scheduleUpdate();
        }
    },

    onClick() {
        this.filterManager._updateView();
    }
});

export default FilterApplierView;

