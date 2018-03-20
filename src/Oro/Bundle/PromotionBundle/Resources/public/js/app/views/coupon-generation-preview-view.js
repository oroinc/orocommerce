define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var BaseView = require('oroui/js/app/views/base/view');
    var CouponGenerationPreviewView;

    CouponGenerationPreviewView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function CouponGenerationPreviewView() {
            CouponGenerationPreviewView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
        },

        /**
         * @param {jquery.Event} event
         */
        onFormFieldChange: function(event) {
            var self = this;
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
