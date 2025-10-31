import BaseView from 'oroui/js/app/views/base/view';
import mediator from 'oroui/js/mediator';
import _ from 'underscore';
import $ from 'jquery';

const CurrencySelectView = BaseView.extend({
    $select: {},

    options: {
        selectors: {
            currency: 'select[name$="[currency]"]'
        }
    },

    events: {
        'change select[name$="[currency]"]': '_triggerUpdateTotals'
    },

    listen: {
        'pricing:currency:load mediator': '_updateCurrency',
        'pricing:refresh:products-tier-prices:before mediator': '_updateContext'
    },

    /**
     * @inheritdoc
     */
    constructor: function CurrencySelectView(options) {
        CurrencySelectView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        this.$select = $(this.options.el).find(this.options.selectors.currency);

        CurrencySelectView.__super__.initialize.call(this, options);
    },

    _updateContext: function(context) {
        context.requestAttributes.currencyId = this.$select.val();
    },

    _updateCurrency: function(callback) {
        callback({currency: this.$select.val()});
    },

    _triggerUpdateTotals: function() {
        this._resetEstimatedShippingAmount();

        mediator.trigger('update:totals');
        mediator.trigger('pricing:load:prices');
        mediator.trigger('entry-point:order:trigger');
        mediator.trigger('pricing:currency:changed', {currency: this.$select.val()});
    },

    _resetEstimatedShippingAmount: function() {
        const $form = this.$el.closest('form');
        $form.find('input[name*="[estimatedShippingCostAmount]"]').val(null);
    }
});

export default CurrencySelectView;
