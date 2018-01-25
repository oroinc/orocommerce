define(function(require) {
    'use strict';

    var QuickAddImportFormComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var QuickAddImportWidget = require('oro/quick-add-import-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');

    QuickAddImportFormComponent = BaseComponent.extend({
        /**
         * @property {String}
         */
        field: 'input:file',

        /**
         * @property {String}
         */
        fieldEvent: 'change',

        /**
         * @property {Boolean}
         */
        isAdoptedFormSubmit: true,

        /**
         * @property {Object}
         */
        widget: null,

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
            var $field = this.$form.find(this.field);

            this.$form.on('submit', _.bind(this._onSubmit, this));
            $field.on(this.fieldEvent, _.bind(this._handleFieldEvent, this));

            if (this.isAdoptedFormSubmit) {
                this.$form.find('input:submit').hide();
            }
        },

        _handleFieldEvent: function(e) {
            $(e.target).closest('form').submit();
        },

        _onSubmit: function(e) {
            e.preventDefault();

            var form = $(e.target);

            form.validate();

            if (!form.valid()) {
                return false;
            }

            this.widget = new QuickAddImportWidget({
                dialogOptions: {
                    title: __('oro.product.frontend.quick_add.import_validation.title'),
                    modal: true,
                    resizable: false,
                    width: 820,
                    autoResize: true,
                    dialogClass: 'ui-dialog-no-scroll quick-add-validation'
                }
            });
            this.widget.on('contentLoad', _.bind(this._onContentLoad, this));

            this.widget.stateEnabled = false;
            this.widget.incrementalPosition = false;
            this.widget.firstRun = false;

            if (this.isAdoptedFormSubmit) {
                this.widget.trigger('adoptedFormSubmit', form);
            }
        },

        _onContentLoad: function() {
            $(this.field, this.$form).val('');
        },

        dispose: function() {
            if (!this.disposed) {
                return;
            }

            delete this.widget;
            delete this.$form;
            QuickAddImportFormComponent.__super__.dispose.call(this);
        }
    });

    return QuickAddImportFormComponent;
});
