define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const formToAjaxOptions = require('oroui/js/tools/form-to-ajax-options');
    const DialogWidget = require('oro/dialog-widget');

    const QuickAddImportWidget = DialogWidget.extend({
        /**
         * @inheritdoc
         */
        constructor: function QuickAddImportWidget(options) {
            QuickAddImportWidget.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            options = _.defaults(options || {}, {
                incrementalPosition: false
            });

            options.dialogOptions = _.defaults(options.dialogOptions || {}, {
                title: __('oro.product.frontend.quick_add.import_validation.title'),
                modal: true,
                resizable: false,
                width: 820,
                autoResize: true,
                dialogClass: 'ui-dialog-no-scroll quick-add-validation'
            });

            this.firstRun = false;

            QuickAddImportWidget.__super__.initialize.call(this, options);
        },

        _onContentLoad: function(content) {
            if (_.has(content, 'redirectUrl')) {
                mediator.execute('redirectTo', {url: content.redirectUrl}, {redirect: true});
                return;
            }

            QuickAddImportWidget.__super__._onContentLoad.call(this, content);
        },

        /**
         * Submits external from and process response widget content
         *
         * @param {jQuery} $form
         */
        loadContentWithFormSubmit: function($form) {
            this.trigger('adoptedFormSubmit', $form);
        },

        /**
         * Uploads file with form's data and process response widget content
         *
         * @param {File} file
         * @param {jQuery} $form
         */
        loadContentWithFileUpload: function(file, $form) {
            const arrayData = $form.formToArray();
            const formData = new FormData();
            const fileFieldName = $form.find('input:file').attr('name');

            for (let i = 0; i < arrayData.length; i++) {
                formData.append(
                    arrayData[i].name,
                    fileFieldName === arrayData[i].name ? file : arrayData[i].value
                );
            }

            _.each(this._getWidgetData(), function(value, name) {
                formData.append(name, value);
            });

            const ajaxOptions = formToAjaxOptions($form, {
                success: this._onContentLoad.bind(this),
                errorHandlerMessage: false,
                error: this._onContentLoadFail.bind(this),
                beforeSend: function(xhr, options) {
                    options.data = formData;
                }
            });

            this.trigger('beforeContentLoad', this);
            this.loading = $.ajax(ajaxOptions);
        }
    });

    return QuickAddImportWidget;
});
