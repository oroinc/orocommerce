/*global define*/
define(function(require) {
    'use strict';

    var QuickAddCopyPasteFormComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var QuickAddImportWidget = require('orob2bproduct/js/app/widget/quick-add-import-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');

    QuickAddCopyPasteFormComponent = BaseComponent.extend({
        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @inheritDoc
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
                    'title': __('orob2b.product.frontend.quick_add.import_validation.title')
                });

                widget.render();
                widget.trigger('beforeContentLoad');

                form.append($('<input>').attr({
                    type: 'hidden',
                    name: '_wid',
                    value: widget.getWid()
                }));

                $.ajax({
                    type: form.attr('method'),
                    url: form.attr('action'),
                    data: form.serialize()
                }).done(function(data) {
                    widget.setContent(data);
                    widget.trigger('contentLoad');
                });
            });
        }
    });

    return QuickAddCopyPasteFormComponent;
});
