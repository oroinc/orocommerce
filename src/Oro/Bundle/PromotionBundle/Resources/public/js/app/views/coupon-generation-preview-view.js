define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const routing = require('routing');
    const BaseView = require('oroui/js/app/views/base/view');

    const CouponGenerationPreviewView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            routeName: 'oro_promotion_coupon_generation_preview',
            codePreviewSelector: '#coupon-code-preview'
        },

        events: {
            'change .promotion-coupon-generation-preview': 'onFormFieldChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function CouponGenerationPreviewView(options) {
            CouponGenerationPreviewView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
        },

        /**
         * @param {jquery.Event} event
         */
        onFormFieldChange: function(event) {
            const self = this;
            if ($(event.target).is($(event.currentTarget))) {
                $.ajax({
                    method: 'POST',
                    url: routing.generate(this.options.routeName),
                    data: this.$el.serialize(),
                    success: function(response) {
                        if (!response.error) {
                            $(self.options.codePreviewSelector).html(response.code);
                        }
                    }
                });
            }
        }
    });

    return CouponGenerationPreviewView;
});
