import BaseView from 'oroui/js/app/views/base/view';

const FrontendRequestProductCollectionView = BaseView.extend({
    autoRender: true,

    requestProductsContainerSelector: '[data-role="request-products-container"]',
    addRequestProductButtonSelector: '[data-role="request-product-add"]',

    constructor: function FrontendRequestProductCollectionView(options) {
        FrontendRequestProductCollectionView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        FrontendRequestProductCollectionView.__super__.initialize.call(this, options);

        if (!this.$el.find(this.requestProductsContainerSelector).children().length) {
            this.$el.find(this.addRequestProductButtonSelector).click();
        }
    },

    render: function() {
        this.initLayout().done(() => {
            this.$el.find('.request-form__content').removeClass('hidden');
            this.$el.find('.view-loading').remove();
        });
    }
});

export default FrontendRequestProductCollectionView;
