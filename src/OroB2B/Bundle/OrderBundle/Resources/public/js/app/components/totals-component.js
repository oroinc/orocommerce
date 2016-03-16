define(function(require) {
    'use strict';

    var TotalsComponent;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('orob2bpricing/js/app/components/totals-component');

    /**
     * @export orob2border/js/app/components/entry-point-component
     * @extends orob2bpricing.app.components.TotalsComponent
     * @class orob2border.app.components.TotalsComponent
     */
    TotalsComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            mediator.on('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.on('entry-point:order:load', this.setTotals, this);
            mediator.on('entry-point:order:load:after', this.hideLoadingMask, this);

            TotalsComponent.__super__.initialize.call(this, options);
        },

        initializeListeners: function() {
            // disable parent listeners
        },

        /**
         * @param {Object} response
         */
        setTotals: function(response) {
            this.render(response);
        },

        /**
         * @param {Object} e
         */
        updateSubtotals: function(e) {
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
