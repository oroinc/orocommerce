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
         * @property {jQuery}
         */
        $form: null,

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$form = this.options._sourceElement;

            this.$form.on('submit', function(e) {
                e.preventDefault();

                var form = $(e.target);

                form.validate();

                if (!form.valid()) {
                    return false;
                }

                var widget = new QuickAddImportWidget({
                    'dialogOptions': {
                        'title': __('orob2b.product.frontend.quick_add.import_validation.title'),
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
        }
    });

    return QuickAddCopyPasteFormComponent;
});
