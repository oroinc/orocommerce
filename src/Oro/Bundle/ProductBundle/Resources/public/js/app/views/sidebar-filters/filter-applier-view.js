import BaseView from 'oroui/js/app/views/base/view';
import applyFilterTemplate from 'tpl-loader!oroproduct/templates/sidebar-filters/filter-applier.html';
import __ from 'orotranslation/js/translator';
import Popper from 'popper';

const FilterApplierView = BaseView.extend({
    /**
     * @inheritdoc
     */
    template: applyFilterTemplate,

    /**
     * @inheritdoc
     */
    className: 'apply-filters',

    buttonOptions: {
        label: __('oro.filter.updateButton.text'),
        classes: 'btn btn--action btn--size-s'
    },

    /**
     * @inheritdoc
     */
    events: {
        'click [data-role="apply"]': 'onClick'
    },

    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat(['buttonOptions']),

    /**
     * @inheritdoc
     */
    constructor: function FilterApplierView(options) {
        FilterApplierView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    getTemplateData: function() {
        const data = FilterApplierView.__super__.getTemplateData.call(this);

        data.buttonOptions = this.buttonOptions;

        return data;
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

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
            placement: 'right',
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

    destroyPopper() {
        if (this.popper) {
            this.popper.destroy();
            this.popper = null;
        }
    },

    updatePosition() {
        if (!this.disposed && this.popper) {
            this.popper.scheduleUpdate();
        }
    },

    /**
     * @param {HTMLElement} popperReference
     */
    stick(popperReference) {
        if (this.disposed) {
            return;
        }

        this.initPopper(popperReference, this.el);
        this.$el.removeClass('hide');
    },

    unstick() {
        if (this.disposed) {
            return;
        }

        this.$el.addClass('hide');
        this.destroyPopper();
    },

    onClick() {
        this.trigger('apply-changes', this);
    }
});

export default FilterApplierView;

