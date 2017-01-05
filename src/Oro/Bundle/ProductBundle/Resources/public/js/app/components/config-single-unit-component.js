define(function(require) {
    'use strict';

    var ConfigHideFieldsComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ConfigHideFieldsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                row_container: '.control-group',
                dependend_checkbox: '[data-depends-on-field="single_unit_mode"]'
            }
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $dependedEl: null,


        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;
            this.$form = this.$el.closest('form');
            var id = this.$el.data('dependency-id');

            this.$dependedEl = this.$form.find('[data-depends-on-field="' + id + '"]');

            this.updateDependentFields();
            this.$el.on('change', $.proxy(this.updateDependentFields, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('change', $.proxy(this.updateDependentFields, this));

            ConfigHideFieldsComponent.__super__.dispose.call(this);
        },

        updateDependentFields: function() {
            var isChecked = this.$el.prop('checked');

            if (isChecked) {
                this.$dependedEl.closest(this.options.selectors.row_container).show();
            } else {
                this.$dependedEl.closest(this.options.selectors.row_container).hide();
            }
        }
    });

    return ConfigHideFieldsComponent;
});
