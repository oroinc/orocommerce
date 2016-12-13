define(function(require) {
    'use strict';

    var QuickAddCopyPasteFormComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var QuickAddImportWidget = require('oro/quick-add-import-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');

    QuickAddCopyPasteFormComponent = BaseComponent.extend({
        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var $form = this.options._sourceElement;
            var $field = $form.find('textarea');

            $form.on('submit', function(e) {
                e.preventDefault();

                var form = $(e.target);

                form.validate();

                if (!form.valid()) {
                    return false;
                }

                var widget = new QuickAddImportWidget({
                    'dialogOptions': {
                        'title': __('oro.product.frontend.quick_add.import_validation.title'),
                        'modal': true,
                        'resizable': false,
                        'width': 820,
                        'autoResize': true,
                        'dialogClass': 'ui-dialog-no-scroll'
                    }
                });

                widget.stateEnabled = false;
                widget.incrementalPosition = false;
                widget.firstRun = false;
                widget.loadContent(form.serialize(), form.attr('method'), form.attr('action'));
            });

            var validator = $form.validate();
            delete validator.settings.onkeyup;//validate only on change/blur/submit

            $field.on('change blur', function() {
                var val = $field.val();
                val = val.replace(/(\n|\r\n|^)\s+/g, '$1')//trim white space in each line start
                    .replace(/\s+(\n|\r\n|$)/g, '$1');//trim white space in each line end
                $field.val(val);
            });

        }
    });

    return QuickAddCopyPasteFormComponent;
});
