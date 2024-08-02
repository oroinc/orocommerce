import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import RequestProductKitConfigureDialogWidget
    from 'ororfp/js/app/widget/frontend-request-product-kit-configuration-dialog-widget';
import routing from 'routing';
import $ from 'jquery';
import _ from 'underscore';

const FrontendRequestProductKitConfigurationEditView = BaseView.extend(_.extend({}, ElementsHelper, {
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'template', 'dialogOptions'
    ]),

    kitItemLineItemSelector: '[data-role="request-product-kit-item-line-item"]',

    elements: {
        editButton: '[data-role="request-product-kit-configuration-edit"]',
        widgetContainer: '[data-role="request-product-kit-configuration-widget"]',
        errorsContainer: 'div.fields-row-error'
    },

    elementsEvents: {
        'editButton onClickEditButton': ['click', 'onClickEditButton']
    },

    /**
     * @inheritdoc
     */
    modelEvents: {
        'productId onChangeProduct': ['change', 'onChangeProduct']
    },

    /**
     * @property {Backbone.Model}
     */
    model: null,

    /**
     * @property {Backbone.Collection}
     */
    kitItemLineItems: null,

    /**
     * @inheritdoc
     */
    constructor: function RequestProductKitConfiguration(options) {
        RequestProductKitConfiguration.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        FrontendRequestProductKitConfigurationEditView.__super__.initialize.call(this, options);

        this.deferredInitializeCheck(options, ['requestProductModel', 'kitItemLineItems']);
    },

    /**
     * @inheritdoc
     */
    deferredInitialize: function(options) {
        this.template = _.template($(this.template).text());

        this.model = options.requestProductModel;
        this.kitItemLineItems = options.kitItemLineItems;
        if (!this.model || !this.kitItemLineItems) {
            return;
        }

        this.initializeElements(options);
        this.initializeSubviews(options);

        this.toggleView();
    },

    /**
     * @param {Object} data
     */
    onChangeProduct: function(data) {
        if (this.disposed) {
            return;
        }

        if (data?.event?.added?.type === 'kit') {
            this.getElement('editButton').trigger('click');
        }

        this.toggleView();
    },

    toggleView: function() {
        this.$el.toggleClass('hidden', !this.model.get('productId') || this.model.get('productType') !== 'kit');
    },

    /**
     * @param {jQuery.Event} event
     */
    onClickEditButton: function(event) {
        event.preventDefault();

        const routeName = $(event.currentTarget).data('routeName');
        const routeParams = $(event.currentTarget).data('routeParams') || {};
        const url = routing.generate(routeName, routeParams);
        const payload = this.$el.closest('[data-role="request-product"]').find(':input').serializeArray();

        this.openDialog(url, payload);
    },

    /**
     * @param {String} url
     * @param {Array} payload
     */
    openDialog: function(url, payload) {
        const dialog = this.subview('dialog', new RequestProductKitConfigureDialogWidget(_.extend({
            method: 'POST',
            url: url,
            data: payload,
            requestProductModel: this.model,
            kitItemLineItems: this.kitItemLineItems
        }, this.dialogOptions || {})));

        dialog.render();

        this.listenToOnce(dialog, {
            success: this.onDialogSubmitSuccess.bind(this),
            close: this.onDialogClose.bind(this)
        });
    },

    /**
     * @param {String} htmlContent
     * @private
     */
    onDialogSubmitSuccess: function(htmlContent) {
        const $widgetContainer = this.getElement('widgetContainer');

        this.kitItemLineItems.each(model => model.trigger('state:softRemove'));

        this.getElement('errorsContainer').remove();

        $widgetContainer.append(htmlContent);
        $widgetContainer.one('content:initialized', () => {
            this.undelegateElementsEvents();
            this.clearElementsCache();
            this.initializeElements();

            this.initializeSubviews({
                requestProductModel: this.model,
                kitItemLineItems: this.kitItemLineItems,
                modelAttr: {
                    isPendingAdd: true
                }
            });

            this.$el.removeClass('hidden');

            this.stopListening(this.subview('dialog'), 'close', this.onDialogClose);
            this.subview('dialog').remove();
        });

        $widgetContainer.trigger('content:changed');
    },

    /**
     * @private
     */
    onDialogClose: function() {
        if (this.model.get('productType') === 'kit' && !this.$el.find(':input').length) {
            this.kitItemLineItems?.reset();
            this.model.set('productId', undefined);
        }
    }
}));

export default FrontendRequestProductKitConfigurationEditView;
