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
            codePreviewSelectors: {
                length: 'oro_action_operation[couponGenerationOptions][codeLength]',
                codeType: 'oro_action_operation[couponGenerationOptions][codeType]',
                codePrefix: 'oro_action_operation[couponGenerationOptions][codePrefix]',
                codeSuffix: 'oro_action_operation[couponGenerationOptions][codeSuffix]',
                dashesSequence: 'oro_action_operation[couponGenerationOptions][dashesSequence]'
            }
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.form =  this.options._sourceElement.closest('form');
            this.form.on('change',  _.bind(this.onCouponGenerationFormChange, this));
        },

        onCouponGenerationFormChange: function(e) {
            var self = this;
            if (_.contains(this.options.codePreviewSelectors, e.target.name)) {
                $.ajax({
                    method: 'POST',
                    url: routing.generate(this.options.routeName, {'couponGenerationData': this.getFieldsData()}),
                    success: function(response) {
                        if (response.codePreview) {
                            $(self.options.codePreviewSelector).html(response.codePreview);
                        }
                    }
                });
            }
        },

        getFieldsData: function() {
            return {
                'codeLength': $('[name="' + this.options.codePreviewSelectors.length + '"]').val(),
                'codeType': $('[name="' + this.options.codePreviewSelectors.codeType + '"]').val(),
                'codePrefix': $('[name="' + this.options.codePreviewSelectors.codePrefix + '"]').val(),
                'codeSuffix': $('[name="' + this.options.codePreviewSelectors.codeSuffix + '"]').val(),
                'dashesSequence': $('[name="' + this.options.codePreviewSelectors.dashesSequence + '"]').val()

            };
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.form.off('change',  _.bind(this.onCouponGenerationFormChange, this));
            CouponGenerationPreviewComponent.__super__.dispose.call(this);
        }
    });

    return CouponGenerationPreviewComponent;
});
