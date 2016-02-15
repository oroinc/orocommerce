/** @lends SwitchableEditableViewComponent */
define(function(require) {
    'use strict';

    var SwitchableEditableViewComponent;
    var InlineEditableViewComponent = require('oroform/js/app/components/inline-editable-view-component');
    var BaseModel = require('oroui/js/app/models/base/model');
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var tools = require('oroui/js/tools');
    var frontendTypeMap = require('oroform/js/tools/frontend-type-map');
    var _ = require('underscore');

    SwitchableEditableViewComponent = InlineEditableViewComponent.extend(/** @exports SwitchableEditableViewComponent.prototype */{
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.options, options);

            this.messages = options.messages;
            this.metadata = options.metadata;
            this.fieldName = options.fieldName;
            this.inlineEditingOptions = options.metadata.inline_editing;
            // frontend type mapped to viewer/editor/reader
            var frontendType = options.hasOwnProperty('frontend_type') ? options.frontend_type : 'text';
            this.classes = frontendTypeMap[frontendType];

            this.model = new BaseModel();
            this.model.set(this.fieldName, options.value);

            var waitors = [];
            waitors.push(tools.loadModuleAndReplace(this.inlineEditingOptions.save_api_accessor, 'class').then(
                _.bind(function() {
                    var ConcreteApiAccessor = this.inlineEditingOptions.save_api_accessor['class'];
                    this.saveApiAccessor = new ConcreteApiAccessor(
                        _.omit(this.inlineEditingOptions.save_api_accessor, 'class'));
                }, this)
            ));

            this.deferredInit = $.when.apply($, waitors);

            this.$el = options._sourceElement;
            this.wrapper = new BaseView({el: this.$el.find('[data-role="editor"]')});

            this.switcher = options._sourceElement.find('[data-role="start-editing"]');
            this.switcher.on('click', _.bind(this.onSwitcherChange, this));

            this.onSwitcherChange();
        },

        onSwitcherChange: function() {
            if (this.switcher.is(':checked')) {
                this.enterEditMode();
            } else {
                this.hideEditor();
            }
        },

        isInsertEditorModeOverlay: function() {
            return false;
        },

        enterEditMode: function() {
            if (!this.editorView) {
                var viewInstance = this.createEditorViewInstance();
                this.initializeEditorListeners(viewInstance);
            }
            this.showEditor();

            return this.editorView;
        },

        exitEditMode: function() {
        },

        hideEditor: function() {
            if (this.editorView) {
                this.editorView.$el.val('');
                this.saveCurrentCell();
            }

            this.wrapper.$el.hide();
        },

        showEditor: function() {
            this.wrapper.$el.show();
        },

        createEditorViewInstance: function() {
            var BaseEditor = this.classes.editor;
            var View = BaseEditor.extend({
                render: function() {
                    this.validator = this.$el.validate({
                        submitHandler: _.bind(function(form, e) {
                            if (e && e.preventDefault) {
                                e.preventDefault();
                            }
                            this.trigger('saveAction');
                        }, this),
                        errorPlacement: function(error, element) {
                            error.appendTo(this.$el);
                        },
                        rules: {
                            value: this.getValidationRules()
                        }
                    });
                    if (this.options.value) {
                        this.setFormState(this.options.value);
                    }
                    this.onChange();
                },

                getValue: function() {
                    return this.$el.val();
                },

                onFocusout: function(e) {
                    if (this.isChanged() && this.validator.form()) {
                        this.trigger('saveAction');
                    }

                    View.__super__.onFocusout.apply(this, arguments);
                }
            });

            this.editorView = new View(this.getEditorOptions());

            return this.editorView;
        },

        getEditorOptions: function() {
            return {
                el: this.wrapper.$(':input:first'),
                autoRender: true,
                model: this.model,
                fieldName: this.fieldName
            };
        }
    });

    return SwitchableEditableViewComponent;
});
