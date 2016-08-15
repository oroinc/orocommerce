define(function(require) {
    'use strict';

    var QuickAddCopyPasteFormComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var QuickAddImportWidget = require('orob2bproduct/js/app/widget/quick-add-import-widget');
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

                var widget = new QuickAddImportWidget({});

                widget.firstRun = false;
                widget.loadContent(form.serialize(), form.attr('method'), form.attr('action'));
            });
        }
    });

    return QuickAddCopyPasteFormComponent;
});
