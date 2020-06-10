import _ from 'underscore';
import $ from 'jquery';
import BaseClass from 'oroui/js/base-class';
import rteActions from './rte';

const ComponentManager = BaseClass.extend({
    typeBuildersOptions: null,

    typeBuilders: [],

    editor: null,

    constructor: function ComponentManager(options) {
        ComponentManager.__super__.constructor.call(this, options);
    },

    /**
     * Create manager
     */
    initialize(options) {
        ComponentManager.__super__.initialize.call(this, options);

        Object.assign(this, _.pick(options, 'editor', 'typeBuildersOptions'));

        this.applyTypeBuilders();
        this.renderRteActions();
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        _.invoke(this.typeBuilders, 'dispose');

        ComponentManager.__super__.dispose.call(this);
    },

    /**
     * Add Rich Text Editor actions
     */
    renderRteActions() {
        const {RichTextEditor} = this.editor;
        const $actionBar = $(RichTextEditor.actionbar);

        [...RichTextEditor.getAll(), ...rteActions]
            .sort((a, b) => a.order - b.order)
            .forEach(item => {
                const {group, name, command, result} = item;
                if (RichTextEditor.get(name)) {
                    RichTextEditor.remove(name);
                }

                if (command && !result) {
                    item.result = rte => rte.exec(command);
                }

                item.editor = this.editor;

                RichTextEditor.add(name, item);

                if (group) {
                    if (!$actionBar.find(`[data-group-by="${group}"]`).length) {
                        $actionBar.append($('<div />', {
                            'data-group-by': group,
                            'class': 'actionbar-group'
                        }));
                    }

                    $(item.btn).appendTo($actionBar.find(`[data-group-by="${group}"]`));
                }
            });
    },

    /**
     * Add components
     */
    applyTypeBuilders() {
        for (const [id, componentType] of Object.entries(ComponentManager.componentTypes)) {
            let options = {
                componentType: id,
                editor: this.editor
            };

            if (componentType.optionNames) {
                const builderOptions = _.pick(this.typeBuildersOptions, componentType.optionNames);

                options = {...builderOptions, ...options};
            }

            const instance = new componentType.Constructor(options);

            instance.execute();
            this.typeBuilders.push(instance);
        }
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
