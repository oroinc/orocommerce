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
        this.show = _.debounce(this.show.bind(this), 0);
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
        if (!referenceEl || !popperEl) {
            return;
        }

        this.destroyPopper();

        this.popper = new Popper(referenceEl, popperEl, {
            placement: _.isRTL() ? 'left' : 'right',
            positionFixed: false,
            removeOnDestroy: false,
            modifiers: {
                offset: {
                    offset: '0, 6'
                },
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
     * {HTMLElement|null}
     */
    getReferenceElementForPopper(filter) {
        const $criteriaEl = filter.$(filter.criteriaSelector);

        if (!$criteriaEl.length) {
            return null;
        }

        const $valueEndEl = $criteriaEl.find(filter.criteriaValueSelectors.value_end);
        const $valueEl = $criteriaEl.find(filter.criteriaValueSelectors.value);

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
    show(filter) {
        if (filter.disposed || !filter.enabled) {
            return;
        }

        const popperReference = this.getReferenceElementForPopper(filter);

        if (!popperReference) {
            return;
        }

        if (!this.rendered) {
            this.render();
        }

        if (!$.contains(filter.el, this.el)) {
            this.$el.appendTo(filter.$el);
        }

        this.initPopper(popperReference, this.el);
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

