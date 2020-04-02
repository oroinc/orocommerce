import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseClass from 'oroui/js/base-class';
import selectTemplate from 'tpl-loader!orocms/templates/grapesjs-select-action.html';

const ComponentManager = BaseClass.extend({
    editorFormats: [
        'bold',
        'italic',
        'underline',
        'strikethrough',
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
        const editor = this.editor;

        this.editorFormats.forEach(format => RichTextEditor.remove(format));

        RichTextEditor.add('formatBlock', {
            icon: selectTemplate({
                options: {
                    normal: __('oro.cms.wysiwyg.format_block.normal'),
                    p: __('oro.cms.wysiwyg.format_block.p'),
                    h1: __('oro.cms.wysiwyg.format_block.h1'),
                    h2: __('oro.cms.wysiwyg.format_block.h2'),
                    h3: __('oro.cms.wysiwyg.format_block.h3'),
                    h4: __('oro.cms.wysiwyg.format_block.h4'),
                    h5: __('oro.cms.wysiwyg.format_block.h5'),
                    h6: __('oro.cms.wysiwyg.format_block.h6')
                },
                name: 'tag'
            }),
            event: 'change',

            attributes: {
                'title': __('oro.cms.wysiwyg.format_block.title'),
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
                    if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'].indexOf(value) !== -1) {
                        select.value = value;
                    } else {
                        select.value = 'normal';
                    }
                }
            }
        });

        RichTextEditor.add('link', {
            icon: '<span class="fa fa-link" aria-hidden="true"></span>',
            name: 'link',
            attributes: {
                title: __('oro.cms.wysiwyg.component.link.label')
            },

            result: (rte, action) => {
                const selection = rte.selection();

                if (action.isSelectionALink(selection)) {
                    const selectedComponent = editor.getSelected();

                    if (selectedComponent.get('type') === 'link') {
                        const el = selectedComponent.view.el;

                        if (el.parentNode) {
                            const textNode = document.createTextNode(selection.toString());

                            el.parentNode.insertBefore(textNode, el);
                        }

                        selectedComponent.destroy();
                    } else {
                        const linkElement = action.getLinkElement(selection);

                        linkElement.classList.remove('link');
                        linkElement.setAttribute('href', '#');
                        rte.exec('unlink');
                    }
                } else if (selection.toString() !== '') {
                    rte.exec('createLink', '#');

                    const linkElement = action.getLinkElement(rte.selection());

                    linkElement.setAttribute('href', '');
                    linkElement.classList.add('link');
                }
            },

            update: (rte, action) => {
                const selection = rte.selection();

                if (action.isSelectionALink(selection)) {
                    action.btn.classList.add(rte.classes.active);
                }
            },

            isSelectionALink: selection => {
                if (!selection.anchorNode) {
                    return false;
                }

                const parentNode = selection.anchorNode.parentNode;

                return parentNode && parentNode.nodeName === 'A' && parentNode.innerHTML === selection.toString();
            },

            getLinkElement: selection => {
                let linkElement = selection.anchorNode;

                if (linkElement.parentElement.tagName === 'A') {
                    linkElement = linkElement.parentElement;
                } else {
                    linkElement = linkElement.nextSibling;
                }

                return linkElement;
            }
        });

        const simpleActions = [{
            command: 'bold',
            icon: '<b aria-hidden="true">B</b>',
            title: __('oro.cms.wysiwyg.simple_actions.bold.title')
        }, {
            command: 'italic',
            icon: '<i aria-hidden="true">I</i>',
            title: __('oro.cms.wysiwyg.simple_actions.italic.title')
        }, {
            command: 'underline',
            icon: '<u aria-hidden="true">U</u>',
            title: __('oro.cms.wysiwyg.simple_actions.underline.title')
        }, {
            command: 'strikethrough',
            icon: '<strike aria-hidden="true">S</strike>',
            title: __('oro.cms.wysiwyg.simple_actions.strikethrough.title')
        }, {
            command: 'insertOrderedList',
            icon: '<span class="fa fa-list-ol" aria-hidden="true"></span>',
            title: __('oro.cms.wysiwyg.simple_actions.insert_ordered_list.title')
        }, {
            command: 'insertUnorderedList',
            icon: '<span class="fa fa-list-ul" aria-hidden="true"></span>',
            title: __('oro.cms.wysiwyg.simple_actions.insert_unordered_list.title')
        }, {
            command: 'subscript',
            icon: '<span class="fa fa-subscript" aria-hidden="true"></span>',
            title: __('oro.cms.wysiwyg.simple_actions.subscript.title')
        }, {
            command: 'superscript',
            icon: '<span class="fa fa-superscript" aria-hidden="true"></span>',
            title: __('oro.cms.wysiwyg.simple_actions.superscript.title')
        }];

        simpleActions.forEach(item => {
            RichTextEditor.add(item.command, {
                icon: item.icon,
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
