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
                row_container: '.control-group'
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
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;
            this.$form = this.$el.closest('form');

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
            var id = this.$el.data('dependency-id');
            var value = this.$el.val();
            _.each(
                this.$form.find('[data-depends-on-field="' + id + '"]'),
                function(item) {
                    var $item = $(item);
                    if ($item.data('depends-on-field-value') == value) {
                        $item.closest(this.options.selectors.row_container).show();
                    } else {
                        $item.closest(this.options.selectors.row_container).hide();
                    }
                },
                this
            );
        }
    });

    return ConfigHideFieldsComponent;
});
