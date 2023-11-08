define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');

    const ProductPriceDebugSidebarViewComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            websitesSelector: '.priceListSelectorContainer',
            customersSelector: '.priceListSelectorContainer',
            dateSelector: '.dateContainer',
            showFullUsedChainSelector: '.showFullUsedChainContainer',
            showDetailedAssignmentInfoSelector: '.showDetailedAssignmentInfoContainer',
            sidebarAlias: 'product-prices-debug-view-sidebar',
            traceViewRoute: 'oro_pricing_price_product_debug_trace',
            productId: null
        },

        /**
         * @inheritdoc
         */
        constructor: function ProductPriceDebugSidebarViewComponent(options) {
            ProductPriceDebugSidebarViewComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('change', this.options.websitesSelector, this.triggerSidebarChanged.bind(this))
                .on('change', this.options.customersSelector, this.triggerSidebarChanged.bind(this))
                .on('change', this.options.dateSelector, this.triggerSidebarChanged.bind(this))
                .on('change', this.options.showFullUsedChainSelector, this.triggerSidebarChanged.bind(this))
                .on(
                    'change',
                    this.options.showDetailedAssignmentInfoSelector,
                    this.triggerSidebarChanged.bind(this)
                );
        },

        triggerSidebarChanged: function() {
            const params = {
                id: this.options.productId,
                website: $(this.options.websitesSelector).val(),
                customer: $(this.options.customersSelector).val(),
                date: $(this.options.dateSelector).val(),
                showFullUsedChain: $(this.options.showFullUsedChainSelector).prop('checked'),
                showDetailedAssignmentInfo: $(this.options.showDetailedAssignmentInfoSelector).prop('checked')
            };

            mediator.execute(
                'redirectTo',
                {url: routing.generate(this.options.traceViewRoute, params)},
                {redirect: false}
            );
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            ProductPriceDebugSidebarViewComponent.__super__.dispose.call(this);
        }
    });

    return ProductPriceDebugSidebarViewComponent;
});
