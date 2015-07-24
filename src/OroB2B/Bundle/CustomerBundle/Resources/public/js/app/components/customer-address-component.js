/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CustomerAddressComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery');

    CustomerAddressComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        targetElement: null,

        initialize: function (options) {
            this.targetElement = $(options._sourceElement);
            if (options.disableDefaultWithoutType) {
                this.disableDefaultWithoutType();
            }

            if (options.disableRepeatedTypes) {
                this.disableRepeatedTypes();
            }
        },

        dispose: function() {
            if (this.disposed || !this.targetElement) {
                return;
            }

            this.targetElement.off('click', '[name$="[defaults][default][]"]');
            this.targetElement.off('click', '[name$="[types][]"]');

            CustomerAddressComponent.__super__.dispose.call(this);
        },

        disableDefaultWithoutType: function () {
            /**
             * Switch off default checkbox when type unselected
             */
            var self = this;
            this.targetElement.on('click', '[name$="[defaults][default][]"]', function() {
                if (this.checked) {
                    self.targetElement.find('[name$="[types][]"][value="' + this.value + '"]').each(function (idx, el) {
                        el.checked = true;
                    });
                }
            });

            this.targetElement.on('click', '[name$="[types][]"]', function() {
                var defaultTypeName = this.name.replace('[types][]', '[defaults][default][]');
                var defaultCheckboxes = self.targetElement.find('[name$="' + defaultTypeName + '"][value="' + this.value + '"]');
                if (!this.checked) {
                    defaultCheckboxes.each(function (idx, el) {
                        el.checked = false;
                    });
                }
            });
        },

        disableRepeatedTypes: function() {
            /**
             * Allow only 1 item with selected type
             */
            var self = this;
            this.targetElement.on('click', '[name$="[defaults][default][]"]', function() {
                if (this.checked) {
                    self.find('[name$="[defaults][default][]"][value="' + this.value + '"]').each(function (idx, el) {
                        el.checked = false;
                    });
                    this.checked = true;
                }
            });
        }
    });

    return CustomerAddressComponent;
});
