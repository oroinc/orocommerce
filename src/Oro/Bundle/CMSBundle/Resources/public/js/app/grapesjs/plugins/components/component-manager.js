import _ from 'underscore';
import BaseClass from 'oroui/js/base-class';

const ComponentManager = BaseClass.extend({
    typeBuildersOptions: null,

    /**
     * Store type builders
     * @property {Array}
     */
    typeBuilders: null,

    editor: null,

    constructor: function ComponentManager(options) {
        this.typeBuilders = [];
        ComponentManager.__super__.constructor.call(this, options);
    },

    /**
     * Create manager
     */
    initialize(options) {
        ComponentManager.__super__.initialize.call(this, options);

        Object.assign(this, _.pick(options, 'editor', 'typeBuildersOptions'));

        this.applyTypeBuilders();
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        _.invoke(this.typeBuilders, 'dispose');

        ComponentManager.__super__.dispose.call(this);
    },

    /**
     * Add components
     */
    applyTypeBuilders() {
        const priority = ([, type]) => {
            return type.Constructor.priority ?? 300;
        };

        Object.entries(ComponentManager.componentTypes)
            .sort(
                (aType, bType) => priority(aType) - priority(bType)
            )
            .forEach(([id, componentType]) => {
                if (this.getTypeBuilder(id)) {
                    return;
                }

                let options = {
                    componentType: id,
                    editor: this.editor
                };

                if (componentType.optionNames) {
                    const builderOptions = _.pick(this.typeBuildersOptions, componentType.optionNames);

                    options = {...builderOptions, ...options};
                }

                const ComponentType = componentType.Constructor;
                const isAllowedContentType = _.isFunction(ComponentType.isAllowed)
                    ? ComponentType.isAllowed(options)
                    : true;

                if (!isAllowedContentType) {
                    this.editor.Components.removeType(id);
                    this.editor.BlockManager.remove(id);
                    return;
                }

                const instance = new ComponentType(options);

                instance.execute();
                this.typeBuilders.push(instance);
            });
    },

    getTypeBuilder(type) {
        return this.typeBuilders.find(({componentType}) => componentType === type);
    }
}, {
    componentTypes: {},
    registerComponentType(id, componentType) {
        if (!id) {
            throw new Error('Param "id" is required');
        }

        if (!_.isObject(componentType) && !_.isFunction(componentType.Constructor)) {
            throw new Error('Param "componentType" has to be an object and has to contain a constructor');
        }

        ComponentManager.componentTypes[id] = componentType;
    },

    registerComponentTypes(componentTypes) {
        if (!_.isObject(componentTypes)) {
            throw new Error('Param "componentTypes" has to be an object');
        }

        Object.entries(componentTypes).forEach(
            ([id, componentType]) => ComponentManager.registerComponentType(id, componentType)
        );
    }
});

export default ComponentManager;
