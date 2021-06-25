define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');

    const CategoryUnitLimitationsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            holderClass: '.category-precision-holder',
            unitSelect: 'select[name$="[unit]"]',
            precisionInput: 'input[name$="[precision]"]'
        },

        /**
         * @inheritdoc
         */
        constructor: function CategoryUnitLimitationsView(options) {
            CategoryUnitLimitationsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$input = this.$el.find(this.options.precisionInput);
            this.$select = this.$el.find(this.options.unitSelect);
            this.$select
                .on('change' + this.eventNamespace(), this.onChange.bind(this))
                .trigger('change');
        },

        /**
         * Handle change select
         */
        onChange: function() {
            if (String(this.$select.val()) === '') {
                this.$input.val('').attr('disabled', true).removeClass('error');
                this.$input.closest('td').find('span[class="validation-failed"]').hide();
            } else {
                this.$input.attr('disabled', false);
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$select.off('change' + this.eventNamespace());

            CategoryUnitLimitationsView.__super__.dispose.call(this);
        }
    });

    return CategoryUnitLimitationsView;
});

