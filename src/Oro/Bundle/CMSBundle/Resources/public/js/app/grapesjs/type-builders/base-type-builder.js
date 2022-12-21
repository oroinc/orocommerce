import {pick, omit} from 'underscore';
import BaseClass from 'oroui/js/base-class';

const BaseTypeBuilder = BaseClass.extend({
    optionNames: ['editor', 'componentType', 'usedTags', 'template'],

    /**
     * wysiwyg model props and methods
     */
    modelMixin: {
        defaults: {
            tagName: 'div'
        }
    },

    /**
     * wysiwyg view props and methods
     */
    viewMixin: {},

    constructor: function BaseTypeBuilder(options) {
        this.usedTags = [];

        BaseTypeBuilder.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        BaseTypeBuilder.__super__.initialize.call(this, options);

        Object.assign(this, pick(options, this.optionNames));

        if (!this.componentType) {
            throw new Error('Option "componentType" is required');
        }

        const {model, view} = this.editor.Components.getType(this.parentType || 'default');

        this.Model = this.createModelConstructor(model);
        this.View = this.createViewConstructor(view);

        this.onInit(options);
    },

    /**
     * Adds component type to editor, adds button to panel, register commands and binds event
     */
    execute() {
        if (!this.validateRestriction()) {
            throw new Error(`Component "${this.componentType}" contains unresolved tags`);
        }

        const dom = this.editor.Components;
        dom.addType(this.componentType, {
            model: this.Model,
            view: this.View
        });
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
        if (this.button) {
            const content = this.template ? this.template(this.getButtonTemplateData()) : {type: this.componentType};

            const panelBtn = this.editor.BlockManager.get(this.componentType);
            const {options = {}} = this.button;
            panelBtn
                ? panelBtn.set({content, ...this.button})
                : this.editor.BlockManager.getAll().add({
                    id: this.componentType,
                    ...this.button,
                    content
                }, options);
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
     * Extend type model
     * @returns {Backbone.Model}
     */
    createModelConstructor(BaseModel) {
        const TypeModel = BaseModel.extend({// eslint-disable-line oro/named-constructor
            ...omit(this.modelMixin, 'defaults'),
            editor: this.editor
        }, pick(this, 'componentType', 'isComponent'));

        Object.defineProperty(TypeModel.prototype, 'defaults', {
            value: {
                ...TypeModel.prototype.defaults,
                ...this.modelMixin.defaults,
                type: this.componentType
            }
        });

        this.constructor.TypeModel = TypeModel;
        return TypeModel;
    },

    /**
     * Extend type view
     * @returns {Backbone.View}
     */
    createViewConstructor(BaseView) {
        const TypeView = BaseView.extend({// eslint-disable-line oro/named-constructor
            ...this.viewMixin,
            editor: this.editor
        });

        this.constructor.TypeView = TypeView;
        return TypeView;
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
            return this.editor.ComponentRestriction.isAllow(this.Model.prototype.defaults.tagName);
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
        const {model: ownModel} = this.editor.Components.getType(this.componentType);
        return model instanceof ownModel;
    }
});

export default BaseTypeBuilder;
