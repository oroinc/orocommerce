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
        const events = {
            [`change ${this.customersSelector}`]: 'triggerSidebarChanged',
            [`change ${this.dateSelector}`]: 'triggerSidebarChanged',
            [`change ${this.showDevelopersInfoSelector}`]: 'triggerSidebarChanged',
            [`change ${this.showDetailedAssignmentInfoSelector}`]: 'triggerSidebarChanged'
        };

        if (this.websitesSelector) {
            events[`change ${this.websitesSelector}`] = 'triggerSidebarChanged';
        }

        return events;
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
            customer: this.$(this.customersSelector).val(),
            date: this.$(this.dateSelector).val(),
            showDevelopersInfo: this.$(this.showDevelopersInfoSelector).prop('checked'),
            showDetailedAssignmentInfo: this.$(this.showDetailedAssignmentInfoSelector).prop('checked')
        };

        if (this.websitesSelector) {
            params['website'] = this.$(this.websitesSelector).val();
        }

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
