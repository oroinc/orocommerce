import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import Modal from 'oroui/js/modal';

const ID_ATTR_VALUE = /id=\"([^"]*?)(?=\")/gi;

/**
 *
 * @param htmlStringLine
 * @param editor
 * @returns {*}
 */
function idValidationConstrain({htmlStringLine, editor}) {
    const editorContent = editor.getHtml();
    const selected = editor.getSelected();
    let selectedIds;
    if (selected) {
        selectedIds = selected.getInnerHTML().match(ID_ATTR_VALUE);
    }
    const matches = htmlStringLine.match(ID_ATTR_VALUE);
    let ids = editorContent.match(ID_ATTR_VALUE);

    if (matches && ids) {
        if (selectedIds) {
            ids = ids.filter(id => !selectedIds.includes(id));
        }
        const found = ids.find(id => matches.includes(id));

        if (found) {
            return __('oro.htmlpurifier.messages.exist_id', {
                id: found.replace('id="', '')
            });
        }
    }
}

const toolbarCommands = [
    {
        id: 'expose-custom-code',
        command: 'expose-custom-code',
        label: '',
        attributes: {
            'label': __('oro.cms.wysiwyg.toolbar.exposeCode'),
            'class': 'fa fa-object-group'
        }
    },
    {
        id: 'edit-custom-code',
        command: 'edit-source-code',
        label: '',
        attributes: {
            'label': __('oro.cms.wysiwyg.toolbar.editCode'),
            'class': 'fa fa-code'
        }
    }
];

const CustomCodeType = BaseType.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.customCode.label'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-code'
        },
        order: 70,
        activate: true
    },

    constructor: function CustomCodeTypeBuilder(...args) {
        return CustomCodeTypeBuilder.__super__.constructor.apply(this, args);
    },

    commands: {
        'edit-source-code': editor => {
            const selected = editor.getSelected();
            const css = editor.getCss({
                component: selected
            });
            const html = selected.getInnerHTML();
            const content = (html ? html : '') + (css ? `<style>${css}</style>` : '');
            const {Commands} = editor;

            if (Commands.has('gjs-open-import-webpage')) {
                return Commands.run('gjs-open-import-webpage', {
                    content,
                    exportButton: false,
                    dialogOptions: {
                        title: 'Source code'
                    },
                    renderProps: {
                        codeValidationOptions: {
                            allowLock: false,
                            constraints: [
                                idValidationConstrain
                            ]
                        },
                        noEscapeStyleTag: true,
                        importCallback({content}) {
                            selected.set('content', content);
                            Commands.stop('gjs-open-import-webpage');
                        }
                    }
                });
            }
        },
        'expose-custom-code': editor => {
            const selected = editor.getSelected();

            const confirm = new Modal({
                autoRender: true,
                className: 'modal oro-modal-danger',
                title: __('oro.cms.wysiwyg.component.customCode.confirmation.title'),
                content: __('oro.cms.wysiwyg.component.customCode.confirmation.desc')
            });

            confirm.on('ok', () => {
                editor.selectRemove(selected);
                const content = selected.get('content');
                const res = selected.replaceWith(
                    /^(\s+)?\<[\s\S]+\>/gi.test(content) ? content : `<div>${content}</div>`
                );
                if (res[0]) {
                    editor.select(res[0]);
                }
            });

            confirm.open();
        }
    },

    modelProps: {
        defaults: {
            tagName: 'div',
            type: 'custom-code',
            name: __('oro.cms.wysiwyg.component.customCode.label'),
            attributes: {
                'data-type': 'custom-source-code'
            },
            copyable: false,
            stylable: false,
            droppable: false,
            traits: [],
            disableSelectorManager: true
        },

        init() {
            const toolbar = this.get('toolbar');
            toolbarCommands.forEach(toolbarCommand => {
                if (!toolbar.find(action => action.id === toolbarCommand.id)) {
                    toolbar.unshift(toolbarCommand);
                }
            });
        }
    },

    viewProps: {
        events: {
            dblclick: 'onActive'
        },

        onActive() {
            const Commands = this.em.get('Commands');

            if (Commands.has('edit-source-code')) {
                Commands.run('edit-source-code');
            }
        },

        onRender() {
            this.$el.css('min-height', 50);
        }
    },

    isComponent(el) {
        if (el.nodeType === Node.ELEMENT_NODE &&
            el.tagName === 'DIV' &&
            el.getAttribute('data-type') === 'custom-source-code'
        ) {
            return {
                type: this.componentType,
                content: el.innerHTML
            };
        }
    }
}, {
    type: 'custom-code'
});

export default CustomCodeType;
