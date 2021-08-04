import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';
import CreateLinkDialog from 'orocms/js/app/grapesjs/dialogs/create-link-dialog';

const linkColor = window.getComputedStyle(document.documentElement).getPropertyValue('--secondary');
const TEMP_ATTR = _.uniqueId('data-temp-');

const LinkTypeBuilder = BaseTypeBuilder.extend({
    commands: {
        'open-create-link-dialog': {
            getAttributes(element) {
                const attrs = {};

                for (const attr of ['title', 'href', 'target']) {
                    const value = element.getAttribute(attr);

                    if (value !== null) {
                        attrs[attr] = value;
                    }
                }

                if (element.innerText) {
                    attrs['text'] = element.innerText;
                }

                return attrs;
            },

            openDialog(editor, link, dialogOptions = {}) {
                dialogOptions = _.defaults(dialogOptions, {
                    unlink: true
                });

                let unlink = dialogOptions.unlink;
                const dialog = new CreateLinkDialog(dialogOptions);

                link.setAttribute(TEMP_ATTR, '');
                dialog.open();
                dialog.on('create-link-dialog:valid', data => {
                    const $link = $(editor.Canvas.getWrapperEl()).find(`[${TEMP_ATTR}]`);
                    const newAttrs = Object.assign({title: ''}, data);

                    for (const [attr, value] of Object.entries(newAttrs)) {
                        if (attr === 'text') {
                            $link.text(value);
                        } else {
                            $link.attr(attr, value);
                        }
                    }

                    editor.selectRemove(editor.getSelected());
                    editor.selectAdd($link[0]);

                    const component = editor.getSelected();

                    if (component) {
                        component.addAttributes(newAttrs);
                    }
                    unlink = false;
                }).on('close hidden', () => {
                    const $link = $(editor.Canvas.getWrapperEl()).find(`[${TEMP_ATTR}]`);

                    $link.removeAttr(TEMP_ATTR);

                    if (unlink) {
                        const textNode = document.createTextNode($link.text().trim());

                        $link.replaceWith(textNode);
                    }

                    dialog.off();
                });
            },

            run: function(editor, sender, opts = {}) {
                const dialogOptions = {
                    ...{controlsValues: this.getAttributes(opts.link)},
                    ...opts.dialogOptions || {}
                };

                this.openDialog(editor, opts.link, dialogOptions);
            }
        }
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        _.extend(this, _.pick(options, 'editor', 'componentType'));
    },

    execute() {
        this.editor.DomComponents.addType(this.componentType, {
            isComponent: el => {
                let result = null;

                if (el.tagName === 'A') {
                    result = {
                        type: this.componentType
                    };
                }

                return result;
            },
            extend: this.componentType,
            model: {
                defaults: {
                    tagName: 'a',
                    classes: ['link'],
                    traits: ['href', 'text', 'title', 'target']
                },
                tempAttr: TEMP_ATTR,

                getAttrToHTML() {
                    const attrs = this.getAttributes();

                    delete attrs.style;
                    delete attrs.onmousedown;
                    delete attrs[this.tempAttr];
                    delete attrs['text'];

                    return attrs;
                }
            },
            extendView: this.componentType,
            extendFnView: ['initialize'],
            view: {
                defaults: {
                    tagName: 'a'
                },
                events: {
                    dblclick: 'onDoubleClick'
                },
                editor: this.editor,
                initialize() {
                    this.listenTo(this.model, 'change:attributes:text', (model, value) => model.components(value));
                },
                onRender() {
                    const traitText = this.model.getTrait('text');

                    if (traitText) {
                        traitText.set('value', this.el.innerText);
                    }
                },
                onDoubleClick: function(e) {
                    e.stopPropagation();

                    this.editor.runCommand('open-create-link-dialog', {
                        link: e.currentTarget,
                        dialogOptions: {
                            unlink: false,
                            title: __('oro.cms.wysiwyg.create_link_dialog.add_edit_link'),
                            okText: __('oro.cms.wysiwyg.create_link_dialog.apply')
                        }
                    });
                }
            }
        });
        this.editor.BlockManager.get('link').set({
            label: __('oro.cms.wysiwyg.component.link.label'),
            content: {
                type: this.componentType,
                components: [{
                    type: 'textnode',
                    content: __('oro.cms.wysiwyg.component.link.content')
                }],
                style: {
                    color: linkColor
                }
            }
        });
        this.registerEditorCommands();
    }
});

export default LinkTypeBuilder;
