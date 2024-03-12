import $ from 'jquery';
import _ from 'underscore';
import routing from 'routing';

const FrontendRequestProductTierPrices = {
    tierPricesRouteName: 'oro_rfp_frontend_request_tier_prices',

    requestTierPricesPayload: [],

    deferred: null,

    requestTierPrices: function(payload) {
        this.requestTierPricesPayload = _.union(this.requestTierPricesPayload, payload);

        if (!this.deferred) {
            this.deferred = $.Deferred();
        }

        this.doDebouncedRequestTierPrices();

        return this.deferred.promise();
    },

    doDebouncedRequestTierPrices: _.debounce(function() {
        this.doRequestTierPrices();
    }, 100),

    doRequestTierPrices: function() {
        const deferred = this.deferred;
        const payload = this.getPayload();

        this.requestTierPricesPayload = [];
        this.deferred = null;

        $.ajax({
            type: 'POST',
            url: this.getTierPricesUrl(),
            data: payload,
            errorHandlerMessage: (event, jqXHR, settings) => {
                return jqXHR.status !== 422;
            },
            success: function(...args) {
                deferred.resolve(...args);
            },
            error: function(...args) {
                deferred.reject(...args);
            }
        });
    },

    getPayload: function() {
        const addedKeys = [];

        // Filters out duplicates from a payload, leaving the most recent ones.
        return this.requestTierPricesPayload
            .reverse()
            .filter(({name}) => {
                if (addedKeys.indexOf(name) !== -1) {
                    return false;
                }

                addedKeys.push(name);

                return true;
            });
    },

    getTierPricesUrl: function() {
        return routing.generate(this.tierPricesRouteName);
    }
};

export default FrontendRequestProductTierPrices;
