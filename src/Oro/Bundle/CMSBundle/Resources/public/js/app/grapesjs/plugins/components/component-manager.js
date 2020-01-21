import _ from 'underscore';
import BaseClass from 'oroui/js/base-class';
import selectTemplate from 'tpl-loader!orocms/templates/grapesjs-select-action.html';

const ComponentManager = BaseClass.extend({
    editorFormats: [
        'link',
        'formatBlock',
        'insertOrderedList',
        'insertUnorderedList',
        'subscript',
        'superscript'
    ],

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
        this.addActionRte();
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
    addActionRte() {
        const RichTextEditor = this.editor.RichTextEditor;

        this.editorFormats.forEach(format => RichTextEditor.remove(format));

        RichTextEditor.add('formatBlock', {
            icon: selectTemplate({
                options: {
                    normal: 'Normal text',
                    h1: 'Heading 1',
                    h2: 'Heading 2',
                    h3: 'Heading 3',
                    h4: 'Heading 4',
                    h5: 'Heading 5',
                    h6: 'Heading 6'
                },
                name: 'tag'
            }),
            event: 'change',

            attributes: {
                'title': 'Text format',
                'class': 'gjs-rte-action text-format-action'
            },

            priority: 0,

            result: function result(rte, action) {
                const value = action.btn.querySelector('[name="tag"]').value;

                if (value === 'normal') {
                    const parentNode = rte.selection().getRangeAt(0).startContainer.parentNode;
                    const text = parentNode.innerText;
                    parentNode.remove();

                    return rte.insertHTML(text);
                }
                return rte.exec('formatBlock', value);
            },

            update: function(rte, action) {
                const value = rte.doc.queryCommandValue(action.name);
                const select = action.btn.querySelector('[name="tag"]');

                if (value !== 'false') {
                    if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].indexOf(value) !== -1) {
                        select.value = value;
                    } else {
                        select.value = 'normal';
                    }
                }
            }
        });

        RichTextEditor.add('link', {
            icon: '<i class="fa fa-link"></i>',
            name: 'link',
            attributes: {
                title: 'Link'
            },
            result: rte => {
                const anchor = rte.selection().anchorNode;
                const nextSibling = anchor && anchor.nextSibling;
                if (nextSibling && nextSibling.nodeName === 'A') {
                    rte.exec('unlink');
                } else {
                    rte.insertHTML(`<a class="link" href="">${rte.selection()}</a>`);
                }
            }
        });

        const simpleActions = [
            {command: 'insertOrderedList', icon: 'fa-list-ol', title: 'Ordered List'},
            {command: 'insertUnorderedList', icon: 'fa-list-ul', title: 'Unordered List'},
            {command: 'subscript', icon: 'fa-subscript', title: 'Subscript'},
            {command: 'superscript', icon: 'fa-superscript', title: 'Superscript'}
        ];

        simpleActions.forEach(item => {
            RichTextEditor.add(item.command, {
                icon: `<i class="fa ${item.icon}"></i>`,
                attributes: {
                    title: item.title
                },
                result: function result(rte, action) {
                    return rte.exec(item.command);
                }
            });
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
