define(function(require) {
    'use strict';

    var CreditCardComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    CreditCardComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            paymentMethod: null,
            selectors: {
                month: '.checkout__form__select_exp-month',
                year: '.checkout__form__select_exp-year',
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

            mediator.on('checkout:place-order:response', this.handleSubmit, this);

            this.$el = this.options._sourceElement;

            this.$el.on('change', this.options.selectors.month, _.bind(this.collectMonthDate, this));
            this.$el.on('change', this.options.selectors.year, _.bind(this.collectYearDate, this));

            $.validator.loadMethod('orob2bpayment/js/validator/creditCardNumberLuhnCheck');
            $.validator.loadMethod('orob2bpayment/js/validator/creditCardExpirationDate');
            $.validator.loadMethod('orob2bpayment/js/validator/creditCardExpirationDateNotBlank');
        },

        handleSubmit: function(eventData) {
            if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
                eventData.stopped = true;
                var data = this.$el.find('[data-gateway]').serializeArray();
                var resolvedEventData = _.extend(
                    {
                        'SECURETOKEN': false,
                        'SECURETOKENID': false,
                        'errorUrl': false,
                        'returnUrl': false,
                        'formAction': false
                    },
                    eventData.responseData
                );

                data.push({name: 'SECURETOKEN', value: resolvedEventData.SECURETOKEN});
                data.push({name: 'SECURETOKENID', value: resolvedEventData.SECURETOKENID});
                data.push({name: 'ERRORURL', value: resolvedEventData.errorUrl});
                data.push({name: 'RETURNURL', value: resolvedEventData.returnUrl});

                this.postUrl(resolvedEventData.formAction, data);
            }
        },

        postUrl: function(formAction, data) {
            var $form = $('<form action="' + formAction + '">');
            _.each(data, function(field) {
                var $field = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', field.name)
                    .val(field.value);

                $form.append($field);
            });

            $form.submit();
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
        },

        dispose: function() {
        }
    });

    return CreditCardComponent;
});
