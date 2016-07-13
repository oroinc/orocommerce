define(function(require) {
    'use strict';

    var CategoryUnitLimitationsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    CategoryUnitLimitationsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            holderClass: '.category-precision-holder',
            unitSelect: 'select[name$="[unit]"]',
            precisionInput: 'input[name$="[precision]"]'
        },

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$input = this.$el.find(this.options.precisionInput);
            this.$select = this.$el.find(this.options.unitSelect);
            this.$select
                .on('change' + this.eventNamespace(), _.bind(this.onChange, this))
                .trigger('change');
        },

        /**
         * Handle change select
         */
        onChange: function() {
            if (this.$select.val() == '') {
                this.$input.val('').attr('disabled', true).removeClass('error');
                this.$input.closest('td').find('span[class="validation-failed"]').hide();
            } else {
                this.$input.attr('disabled', false);
            }
        },

        /**
         * {@inheritDoc}
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

