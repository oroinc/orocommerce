define(function (require) {
    'use strict';

    var CategoryUnitLimitationsComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery');

    CategoryUnitLimitationsComponent = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            holderClass: '.category-precision-holder',
            unitSelect: 'select[name$="[unit]"]',
            precisionInput: 'input[name$="[precision]"]'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement.find(this.options.unitSelect)
                .on('change', _.bind(this.onChange, this));
            this.options._sourceElement.find(this.options.unitSelect)
                .trigger('change');
        },

        onChange: function() {
            var select = this.options._sourceElement.find(this.options.unitSelect);
            var input = this.options._sourceElement.find(this.options.precisionInput);
            if ($(select).val() == '') {
                $(input).val('').attr('disabled', true);
            } else {
                $(input).attr('disabled', false);
            }
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            CategoryUnitLimitationsComponent.__super__.dispose.call(this);
        }
    });

    return CategoryUnitLimitationsComponent;
});

