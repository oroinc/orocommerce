import routing from 'routing';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';

const ProductPriceDebugDetailsSidebarView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'websitesSelector', 'customersSelector', 'dateSelector',
        'showDevelopersInfoSelector', 'showDetailedAssignmentInfoSelector',
        'sidebarAlias', 'traceViewRoute', 'productId'
    ]),

    websitesSelector: null,

    customersSelector: null,

    dateSelector: null,

    showDevelopersInfoSelector: null,

    showDetailedAssignmentInfoSelector: null,

    sidebarAlias: 'product-prices-debug-view-sidebar',

    traceViewRoute: 'oro_pricing_price_product_debug_trace',

    productId: null,

    events() {
        return {
            [`change ${this.websitesSelector}`]: 'triggerSidebarChanged',
            [`change ${this.customersSelector}`]: 'triggerSidebarChanged',
            [`change ${this.dateSelector}`]: 'triggerSidebarChanged',
            [`change ${this.showDevelopersInfoSelector}`]: 'triggerSidebarChanged',
            [`change ${this.showDetailedAssignmentInfoSelector}`]: 'triggerSidebarChanged'
        };
    },

    /**
     * @inheritdoc
     */
    constructor: function ProductPriceDebugDetailsSidebarView(...args) {
        ProductPriceDebugDetailsSidebarView.__super__.constructor.apply(this, args);
    },

    triggerSidebarChanged: function() {
        const params = {
            id: this.productId,
            website: this.$(this.websitesSelector).val(),
            customer: this.$(this.customersSelector).val(),
            date: this.$(this.dateSelector).val(),
            showDevelopersInfo: this.$(this.showDevelopersInfoSelector).prop('checked'),
            showDetailedAssignmentInfo: this.$(this.showDetailedAssignmentInfoSelector).prop('checked')
        };

        mediator.execute(
            'redirectTo',
            {
                url: routing.generate(this.traceViewRoute, params)
            },
            {
                redirect: false
            }
        );
    }
});

export default ProductPriceDebugDetailsSidebarView;
