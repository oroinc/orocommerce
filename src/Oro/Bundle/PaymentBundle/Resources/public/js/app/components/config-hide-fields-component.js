define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const ConfigHideFieldsComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function ConfigHideFieldsComponent(options) {
            ConfigHideFieldsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;
            this.$form = this.$el.closest('form');

            this.updateDependentFields();
            this.$el.on('change.' + this.cid, this.updateDependentFields.bind(this));
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('.' + this.cid);

            ConfigHideFieldsComponent.__super__.dispose.call(this);
        },

        updateDependentFields: function() {
            const id = this.$el.data('dependency-id');
            const value = this.$el.val();
            _.each(
                this.$form.find('[data-depends-on-field="' + id + '"]'),
                function(item) {
                    const $item = $(item);
                    if ($item.data('depends-on-field-value') === value) {
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
