import _, {throttle} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import NumberFormatter from 'orolocale/js/formatter/number';
import messenger from 'oroui/js/messenger';
import routing from 'routing';
import $ from 'jquery';

const FrontendRequestProductKitConfigurationPriceView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'formSelector', 'productSelector', 'quantitySelector', 'formattedPriceSelector', 'routeName', 'routeParams',
        'loadingMaskClass'
    ]),

    formSelector: undefined,
    productSelector: '[data-role="kit-item-line-item-product"]',
    quantitySelector: '[data-role="kit-item-line-item-quantity"]',
    formattedPriceSelector: '[data-role="formatted-price"]',
    routeName: 'oro_rfp_frontend_request_product_kit_configuration_price',
    routeParams: {},
    loadingMaskClass: 'loading-blur',

    _activeAjaxActions: 0,

    constructor: function FrontendRequestProductKitConfigurationPriceView(options) {
        this.refreshPrice = throttle(this.refreshPrice.bind(this), 20);

        FrontendRequestProductKitConfigurationPriceView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        if (options.formSelector === undefined) {
            throw new Error('Option "formSelector" is required for FrontendRequestProductKitConfigurationPriceView');
        }

        FrontendRequestProductKitConfigurationPriceView.__super__.initialize.call(this, options);

        this.refreshPrice();
    },

    /**
     * @inheritdoc
     */
    delegateEvents(events) {
        FrontendRequestProductKitConfigurationPriceView.__super__.delegateEvents.call(this, events);

        $(this.formSelector)
            .on(`change${this.eventNamespace()}`, this.productSelector, this.refreshPrice)
            .on(`change${this.eventNamespace()}`, this.quantitySelector, this.refreshPrice);
    },

    /**
     * @inheritdoc
     */
    undelegateEvents() {
        $(this.formSelector).off(this.eventNamespace());

        FrontendRequestProductKitConfigurationPriceView.__super__.undelegateEvents.call(this);
    },

    refreshPrice() {
        const $form = $(this.formSelector);
        const data = $form.serializeArray();

        if (!this._activeAjaxActions) {
            this._activeAjaxActions = 0;
        }

        $.ajax({
            type: 'POST',
            url: routing.generate(this.routeName, this.routeParams || {}),
            data: data,
            errorHandlerMessage: false,
            beforeSend: () => {
                this._activeAjaxActions++;
                this.$el.addClass(this.loadingMaskClass);
            },
            success: response => {
                if (this.disposed) {
                    return;
                }

                const formattedPrice = response.price
                    ? NumberFormatter.formatCurrency(response.price.value, response.price.currency)
                    : _.__('N/A');

                this.$el.find(this.formattedPriceSelector).text(formattedPrice);
            },
            complete: jqXHR => {
                if (this.disposed) {
                    return;
                }

                this._activeAjaxActions--;
                if (this._activeAjaxActions === 0) {
                    this.$el.removeClass(this.loadingMaskClass);
                }

                if (jqXHR.responseJSON?.messages) {
                    Object.entries(jqXHR.responseJSON?.messages).forEach(([type, messages]) => {
                        messages.forEach(message => messenger.notificationMessage(type, message));
                    });
                }
            }
        });
    }
});

export default FrontendRequestProductKitConfigurationPriceView;
