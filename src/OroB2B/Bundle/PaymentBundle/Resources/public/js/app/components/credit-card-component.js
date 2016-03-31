define(function(require) {
    'use strict';

    var CreditCardComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

    CreditCardComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                month: '.checkout__form__select_exp-month',
                year: '.checkout__form__select_exp-day',
                hiddenDate: 'input[name="EXPDATE"]'
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property string
         */
        month: null,

        /**
         * @property string
         */
        year: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;

            this.$el.find(this.options.selectors.month).on('change', _.bind(this.collectMonthDate, this));
            this.$el.find(this.options.selectors.year).on('change', _.bind(this.collectYearDate, this));

            $.validator.loadMethod('orob2bpayment/js/validator/creditCardNumberLuhnCheck');
        },

        collectMonthDate: function(e) {
            this.month = e.target.value;

            this.setExpirationDate();
        },

        collectYearDate: function(e) {
            this.year = e.target.value;

            this.setExpirationDate();
        },

        setExpirationDate: function() {
            var hiddenExpirationDate = this.$el.find(this.options.selectors.hiddenDate);
            if (this.month && this.year) {
                hiddenExpirationDate.val(this.month + this.year);
            } else {
                hiddenExpirationDate.val('');
            }
        }
    });

    return CreditCardComponent;
});