/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var InvoiceView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    InvoiceView = BaseView.extend({
        options: {
            currencySelector: '.invoice-currency select'
        },

        /**
         * @property {jQuery.Element}
         */
        $currency: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.$currency = $(options.currencySelector);
            this.delegate('change', options.currencySelector, _.bind(this._onCurrencyChange, this));
        },

        _onCurrencyChange: function () {
            mediator.trigger('update:currency', this.$currency.val());
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.undelegate('change', this.options.currencySelector, _.bind(this._onCurrencyChange, this));

            InvoiceView.__super__.dispose.call(this);
        }
    });

    return InvoiceView;
});