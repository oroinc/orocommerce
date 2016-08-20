define(function(require) {
    'use strict';

    var TotalsComponent;
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseComponent = require('oropricing/js/app/components/totals-component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    /**
     * @export oroorder/js/app/components/totals-component
     * @extends oropricing.app.components.TotalsComponent
     * @class oroorder.app.components.TotalsComponent
     */
    TotalsComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            mediator.on('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.on('entry-point:order:load', this.setTotals, this);
            mediator.on('entry-point:order:load:after', this.hideLoadingMask, this);

            mediator.on('line-items-totals:update', this.updateTotals, this);

            this.$totals = this.options._sourceElement.find(this.options.selectors.totals);
            this.template = _.template($(this.options.selectors.template).text());
            this.noDataTemplate = _.template($(this.options.selectors.noDataTemplate).text());
            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});

            this.setTotals(options);
        },

        /**
         * @param {Object} data
         */
        setTotals: function(data) {
            var totals = _.defaults(data, {totals: {total: {}, subtotals: {}}}).totals;

            mediator.trigger('entry-point:order:trigger:totals', totals);

            TotalsComponent.__super__.triggerTotalsUpdateEvent.call(this, data.totals);

            this.render(totals);
        },

        /**
         * @inheritDoc
         */
        updateTotals: function() {
            mediator.trigger('entry-point:order:trigger');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.off('entry-point:order:load', this.setTotals, this);
            mediator.off('entry-point:order:load:after', this.hideLoadingMask, this);

            TotalsComponent.__super__.dispose.call(this);
        }
    });

    return TotalsComponent;
});
