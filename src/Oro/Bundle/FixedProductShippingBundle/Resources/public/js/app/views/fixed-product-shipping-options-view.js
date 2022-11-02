define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');

    const FixedProductShippingOptionsView = BaseView.extend({
        options: {
            surchargeTypeSelector: 'select.fixed-product-surcharge-type',
            updateFlag: null
        },

        $form: null,

        listen: {
            'page:afterChange mediator': '_onPageAfterChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function FixedProductShippingOptionsView(options) {
            FixedProductShippingOptionsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$form = $(this.el).closest('form');
            this.$form.on('change', this.options.surchargeTypeSelector, this.updateFixedProductOptions.bind(this));
        },

        updateFixedProductOptions: function() {
            const data = this.$form.serializeArray();

            data.push({
                name: this.options.updateFlag,
                value: true
            });

            mediator.execute('submitPage', {
                url: this.$form.attr('action'),
                type: this.$form.attr('method'),
                data: $.param(data)
            });
        },

        _onPageAfterChange: function() {
            const surchargeType = this.$form.find(this.options.surchargeTypeSelector);
            surchargeType.focus();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$form.off(this.eventNamespace());
            delete this.options;
            delete this.$form;

            FixedProductShippingOptionsView.__super__.dispose.call(this);
        }
    });

    return FixedProductShippingOptionsView;
});
