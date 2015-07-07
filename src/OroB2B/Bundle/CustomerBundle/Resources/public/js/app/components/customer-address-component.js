/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CustomerAddressComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery');

    CustomerAddressComponent = BaseComponent.extend({
        initialize: function (options) {
            var targetElement = $(options._sourceElement);
            if (options.disableDefaultWithoutType) {
                this.disableDefaultWithoutType(targetElement);
            }

            if (options.disableRepeatedTypes) {
                this.disableRepeatedTypes(targetElement);
            }
        },

        disableDefaultWithoutType: function (targetElement) {
            /**
             * Switch off default checkbox when type unselected
             */
            targetElement.on('click', '[name$="[defaults][default][]"]', function() {
                if (this.checked) {
                    targetElement.find('[name$="[types][]"][value="' + this.value + '"]').each(function (idx, el) {
                        el.checked = true;
                    });
                }
            });

            targetElement.on('click', '[name$="[types][]"]', function() {
                var defaultTypeName = this.name.replace('[types][]', '[defaults][default][]');
                var defaultCheckboxes = targetElement.find('[name$="' + defaultTypeName + '"][value="' + this.value + '"]');
                if (!this.checked) {
                    defaultCheckboxes.each(function (idx, el) {
                        el.checked = false;
                    });
                }
            });
        },

        disableRepeatedTypes: function(targetElement) {
            /**
             * Allow only 1 item with selected type
             */
            targetElement.on('click', '[name$="[defaults][default][]"]', function() {
                if (this.checked) {
                    targetElement.find('[name$="[defaults][default][]"][value="' + this.value + '"]').each(function (idx, el) {
                        el.checked = false;
                    });
                    this.checked = true;
                }
            });
        }
    });

    return CustomerAddressComponent;
});
