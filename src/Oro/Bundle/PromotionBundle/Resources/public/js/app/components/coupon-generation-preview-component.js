define(function(require) {
    'use strict';

    var CouponGenerationPreviewComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var BaseComponent = require('oroui/js/app/components/base/component');

    CouponGenerationPreviewComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            routeName: 'oro_promotion_coupon_generation_preview',
            codePreviewSelector: '#coupon-code-preview',
            codePreviewFieldsSelector: '.promotion-coupon-generation-preview'
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        bindedOnFormFieldChange: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.form = this.options._sourceElement.closest('form');
            this.bindedOnFormFieldChange = _.bind(this.onFormFieldChange, this);
            this.form.on('change', this.options.codePreviewFieldsSelector, this.bindedOnFormFieldChange);
        },

        onFormFieldChange: function(e) {
            var self = this;
            if ($(e.target).is($(e.currentTarget))) {
                $.ajax({
                    method: 'POST',
                    url: routing.generate(this.options.routeName),
                    data: this.form.serialize(),
                    success: function(response) {
                        if (!response.error) {
                            $(self.options.codePreviewSelector).html(response.code);
                        }
                    }
                });
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.form.find(this.options.codePreviewFieldsSelector).off('change', this.bindedOnFormFieldChange);
            CouponGenerationPreviewComponent.__super__.dispose.call(this);
        }
    });

    return CouponGenerationPreviewComponent;
});
