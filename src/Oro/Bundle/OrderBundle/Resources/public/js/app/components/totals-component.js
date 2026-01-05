import mediator from 'oroui/js/mediator';
import _ from 'underscore';
import PricingTotalsComponent from 'oropricing/js/app/components/totals-component';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';

/**
 * @export oroorder/js/app/components/totals-component
 * @extends oropricing.app.components.TotalsComponent
 * @class oroorder.app.components.TotalsComponent
 */
const TotalsComponent = PricingTotalsComponent.extend({
    /**
     * @property {Object}
     */
    currentTotals: {},

    /**
     * @inheritdoc
     */
    constructor: function TotalsComponent(options) {
        TotalsComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this._deferredInit();
        Promise.all(Object.values(options._subPromises).filter(Boolean)).then(() => {
            this.handleSubLayoutInit();
            this._resolveDeferredInit();
        });

        this.options = _.defaults(options || {}, this.options);
    },

    /**
     * Handles sub-layout initialization
     */
    handleSubLayoutInit: function() {
        this.currentTotals = this._getDefaultTotals();

        this.listenTo(mediator, {
            'entry-point:order:load:before': this.showLoadingMask,
            'entry-point:order:load': this.setTotals,
            'entry-point:order:load:after': this.hideLoadingMask,

            'line-items-totals:update': this.updateTotals,
            'shipping-cost:updated': this.setTotals,
            'order:totals:get:current': this.getCurrentTotals,
            'order:totals:push:current': this.pushCurrentTotals
        });

        this.$totals = this.options._sourceElement.find(this.options.selectors.totals);

        this.resolveTemplates();

        this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});

        this.setTotals(this.options);
    },

    _getDefaultTotals: function() {
        return {totals: {total: {}, subtotals: {}}};
    },

    /**
     * @param {Object} data
     */
    getCurrentTotals: function(data) {
        data.result = this.currentTotals;
    },

    /**
     * @param {Object} data
     */
    setTotals: function(data) {
        this.currentTotals = _.defaults(data, this._getDefaultTotals()).totals;

        mediator.trigger('entry-point:order:trigger:totals', this.currentTotals);

        TotalsComponent.__super__.triggerTotalsUpdateEvent.call(this, data.totals);

        this.render(this.currentTotals);
    },

    pushCurrentTotals: function() {
        TotalsComponent.__super__.triggerTotalsUpdateEvent.call(this, this.options.totals);
        mediator.trigger('page:afterPagePartChange');
    },

    /**
     * @inheritdoc
     */
    updateTotals: function() {
        mediator.trigger('entry-point:order:trigger');
    }
});

export default TotalsComponent;
