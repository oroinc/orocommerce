import {pick} from 'underscore';
import BaseClass from 'oroui/js/base-class';

const BaseType = BaseClass.extend({
    optionNames: ['editor', 'componentType', 'usedTags', 'template'],

    /**
     * Define type model as object definition
     * @property {object} modelProps
     */
    modelProps: null,

    /**
     * Define type view as object definition
     * @property {object} viewProps
     */
    viewProps: null,

    /**
     * Define model as class constructor
     * @property {function} TypeModel
     */
    TypeModel: null,

    /**
     * Define view as class constructor
     * @property {function} TypeModel
     */
    TypeView: null,

    constructor: function BaseType(options) {
        this.usedTags = [];

        BaseType.__super__.constructor.call(this, options);
    },

    /**
      * @inheritdoc
      */
    initialize(options) {
        BaseType.__super__.initialize.call(this, options);

        Object.assign(this, pick(options, this.optionNames));

        if (!this.componentType) {
            throw new Error('Option "componentType" is required');
        }

        if (!this.isComponent || typeof this.isComponent !== 'function') {
            throw new Error(`"isComponent" function is required and should be defined for "${this.componentType}"`);
        }

        this.onInit(options);
    },

    /**
     * Create and add new type to the editor
     */
    createType() {
        const {Components} = this.editor;

        Components.addType(this.componentType, this.getTypeDefinition());

        const {model, view} = Components.getType(this.componentType);

        this.Model = model;
        this.View = view;
        this.Model.componentType = this.componentType;
    },

    /**
     * Get component type name
     * @returns {string}
     */
    getType() {
        return this.editor.Components.getType(this.componentType);
    },

    /**
     * Collect type model and view properties
     * @returns {object}
     */
    getTypeDefinition() {
        const {Components} = this.editor;
        const parentType = Components.getType(this.parentType || 'default');

        return {
            ...this.getModelDefinition(parentType.model),
            ...this.getViewDefinition(parentType.view)
        };
    },

    /**
     *
     * @param ParentModel
     * @returns {{extend: *, extendFn: *, model: (*&{editor}), isComponent}|{}|{model: *}}
     */
    getModelDefinition(ParentModel) {
        if (this.TypeModel && typeof this.TypeModel === 'function') {
            const model = this.TypeModel(ParentModel, this.getTypeModelOptions());

            if (this.isComponent) {
                model.isComponent = this.isComponent;
            } else if (ParentModel.isComponent) {
                model.isComponent = ParentModel.isComponent;
            }

            model.componentType = this.componentType;

            return {model};
        }

        if (this.modelProps && typeof this.modelProps === 'object') {
            const {extend = this.parentType, extendFn = [], ...modelProps} = this.modelProps;

            return {
                model: {
                    ...modelProps,
                    ...this.getTypeModelOptions(),
                    editor: this.editor
                },
                isComponent: this.isComponent,
                extend,
                extendFn: [...extendFn]
            };
        }

        return {};
    },

    getViewDefinition(ParentView) {
        if (this.TypeView && typeof this.TypeView === 'function') {
            return {
                view: this.TypeView(ParentView, this.getTypeViewOptions())
            };
        }

        if (this.viewProps && typeof this.viewProps === 'object') {
            const {extendView = this.parentType, extendFnView = [], ...viewProps} = this.viewProps;

            return {
                view: {
                    ...viewProps,
                    ...this.getTypeViewOptions()
                },
                extendView,
                extendFnView: [...extendFnView]
            };
        }

        return {};
    },

    getTypeModelOptions() {
        return {
            editor: this.editor
        };
    },

    getTypeViewOptions() {
        return {
            editor: this.editor
        };
    },

    /**
     * Adds component type to editor, adds button to panel, register commands and binds event
     */
    execute() {
        this.createType();

        if (!this.validateRestriction()) {
            throw new Error(`Component "${this.componentType}" contains unresolved tags`);
        }

        this.createPanelButton();
        this.bindEditorEvents();
        this.registerEditorCommands();
    },

    /**
     * After init callback
     * @param options
     */
    onInit(options) {},

    /**
     * Create and add button to panel
     */
    createPanelButton() {
        const {Blocks} = this.editor;

        if (this.button) {
            const content = this.template ? this.template(this.getButtonTemplateData()) : {type: this.componentType};

            if (typeof content === 'object' && this.button.defaultStyle) {
                content.style = this.button.defaultStyle;
            }

            const blocks = Blocks.getAll();
            blocks.comparator = 'order';

            const panelBtn = Blocks.get(this.componentType);
            const {options = {}} = this.button;

            panelBtn
                ? panelBtn.set({content, ...this.button}, options)
                : blocks.add({
                    id: this.componentType,
                    ...this.button,
                    content
                }, options);

            blocks.sort();
        }
    },

    /**
     * Generate template data
     * @returns {Object}
     */
    getButtonTemplateData() {
        return {};
    },

    /**
     * Validate component template
     * @returns {boolean}
     */
    validateRestriction() {
        if (this.template) {
            return this.editor.ComponentRestriction.checkTemplate(this.template());
        } else if (this.usedTags.length) {
            return this.editor.ComponentRestriction.isAllow(this.usedTags);
        } else {
            const {model} = this.getType();
            return this.editor.ComponentRestriction.isAllow(model.prototype.defaults.tagName);
        }
    },

    /**
     * Binds editor events
     */
    bindEditorEvents() {
        if (!this.editorEvents) {
            return;
        }

        for (const [event, callback] of Object.entries(this.editorEvents)) {
            this.listenTo(this.editor, event, this[callback].bind(this));
        }
    },

    /**
     * Registers editor commands
     */
    registerEditorCommands() {
        if (!this.commands) {
            return;
        }

        for (const [command, callback] of Object.entries(this.commands)) {
            if (this.editor.Commands.has(command)) {
                throw new Error(`Command "${command}" is already registered`);
            }
            this.editor.Commands.add(command, callback);
        }
    },

    /**
     * Check if component owner the model
     * @param {Model} model
     * @returns {boolean}
     */
    isOwnModel(model) {
        const {model: ownModel} = this.getType();
        return model instanceof ownModel;
    }
});

export default BaseType;
