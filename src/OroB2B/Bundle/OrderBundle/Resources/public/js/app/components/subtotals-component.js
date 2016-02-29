define(function(require) {
    'use strict';

    var SubtotalsComponent;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('orob2bpricing/js/app/components/subtotals-component');

    /**
     * @export orob2border/js/app/components/entry-point-component
     * @extends orob2bpricing.app.components.SubtotalsComponent
     * @class orob2border.app.components.SubtotalsComponent
     */
    SubtotalsComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            mediator.on('entry-point:order:load:before', this.showLoadingMask, this);
            mediator.on('entry-point:order:load', this.setSubtotals, this);
            mediator.on('entry-point:order:load:after', this.hideLoadingMask, this);

            SubtotalsComponent.__super__.initialize.call(this, options);
        },

        initializeListeners: function() {
            // disable parent listeners
        },

        /**
         * @param {Object} response
         */
        setSubtotals: function(response) {
            this.render(response.subtotals);
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
            mediator.off('entry-point:order:load', this.setSubtotals, this);
            mediator.off('entry-point:order:load:after', this.hideLoadingMask, this);

            SubtotalsComponent.__super__.dispose.call(this);
        }
    });

    return SubtotalsComponent;
});
