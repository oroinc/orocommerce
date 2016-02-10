/** @lends SwitchableEditableViewComponent */
define(function(require) {
    'use strict';

    var SwitchableEditableViewComponent;
    var InlineEditableViewComponent = require('oroform/js/app/components/inline-editable-view-component');
    var BaseModel = require('oroui/js/app/models/base/model');
    var $ = require('jquery');
    var tools = require('oroui/js/tools');
    var frontendTypeMap = require('../../tools/frontend-type-map');
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
            this.insertEditorMethod = options.insertEditorMethod;
            this.inlineEditingOptions = options.metadata.inline_editing;
            // frontend type mapped to viewer/editor/reader
            var frontendType = options.hasOwnProperty('frontend_type') ? options.frontend_type : 'attached';
            this.classes = frontendTypeMap[frontendType];

            this.model = new BaseModel();
            this.model.set(this.fieldName, options.value);

            this.switcher = options._sourceElement.find('[data-role="start-editing"]');
            this.switcher.on('click', _.bind(this.onSwitcherChange, this));

            var waitors = [];
            waitors.push(tools.loadModuleAndReplace(this.inlineEditingOptions.save_api_accessor, 'class').then(
                _.bind(function() {
                    var ConcreteApiAccessor = this.inlineEditingOptions.save_api_accessor['class'];
                    this.saveApiAccessor = new ConcreteApiAccessor(
                        _.omit(this.inlineEditingOptions.save_api_accessor, 'class'));
                }, this)
            ));

            this.deferredInit = $.when.apply($, waitors);
            this.onSwitcherChange();
        },

        onSwitcherChange: function() {
            if (this.switcher.is(':checked')) {
                this.enterEditMode();
            } else {
                this.exitEditMode();
            }
        },

        isInsertEditorModeOverlay: function() {
            return false;
        },

        enterEditMode: function() {
            if (!this.view.disposed && this.view.$el) {
                this.view.$el.removeClass('save-fail');
            }

            var viewInstance = this.createEditorViewInstance();
            this.initializeEditorListeners(viewInstance);

            return viewInstance;
        },

        createEditorViewInstance: function() {
            var View = this.classes.editor;

            this.editorView = new View(this.getEditorOptions());

            return this.editorView;
        }
    });

    return SwitchableEditableViewComponent;
});
