import _ from 'underscore';
import BaseClass from 'oroui/js/base-class';

const BaseTypeBuilder = BaseClass.extend({
    /**
     * @property
     */
    editor: null,

    /**
     * @property
     */
    parentType: null,

    /**
     * @property
     */
    componentType: null,

    /**
     * @property
     */
    button: null,

    template: null,

    usedTags: [],

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

    Model: null,

    View: null,

    /**
     * wysiwyg commands list
     */
    commands: {},

    /**
     * wysiwyg events list
     */
    editorEvents: {},

    constructor: function BaseTypeBuilder(options) {
        BaseTypeBuilder.__super__.constructor.call(this, options);
    },

    /**
      * @inheritdoc
      */
    initialize(options) {
        BaseTypeBuilder.__super__.initialize.call(this, options);

        _.extend(this, _.pick(options, 'editor', 'componentType', 'usedTags', 'template'));

        if (!this.componentType) {
            throw new Error('Option "componentType" is required');
        }

        const parentTypeComponent = this.editor.DomComponents.getType(this.parentType || 'default');

        this.Model = this.createModelConstructor(parentTypeComponent.model);
        this.View = this.createViewConstructor(parentTypeComponent.view);

        this.onInit(options);
    },

    /**
     * Adds component type to editor, adds button to panel, register commands and binds event
     */
    execute() {
        if (!this.validateRestriction()) {
            throw new Error(`Component "${this.componentType}" contains unresolved tags`);
        }

        const dom = this.editor.DomComponents;

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
            panelBtn
                ? panelBtn.set({content, ...this.button})
                : this.editor.BlockManager.add(this.componentType, {...this.button, content});
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
        return BaseModel.extend({// eslint-disable-line oro/named-constructor
            defaults: {...BaseModel.prototype.defaults, ...this.modelMixin.defaults},
            ..._.omit(this.modelMixin, 'defaults'),
            editor: this.editor
        }, _.pick(this, 'componentType', 'isComponent'));
    },

    /**
     * Extend type view
     * @returns {Backbone.View}
     */
    createViewConstructor(BaseView) {
        return BaseView.extend({// eslint-disable-line oro/named-constructor
            ...this.viewMixin,
            editor: this.editor
        });
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
        for (const [event, callback] of Object.entries(this.editorEvents)) {
            this.listenTo(this.editor, event, this[callback].bind(this));
        }
    },

    /**
     * Registers editor commands
     */
    registerEditorCommands() {
        for (const [command, callback] of Object.entries(this.commands)) {
            this.editor.Commands.add(command, callback);
        }
    }
});

export default BaseTypeBuilder;
