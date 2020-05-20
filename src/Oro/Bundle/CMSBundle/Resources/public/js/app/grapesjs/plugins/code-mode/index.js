import {pick} from 'underscore';
import GrapesJS from 'grapesjs';
import Modal from 'oroui/js/modal';
import __ from 'orotranslation/js/translator';
import styleManagerModule from 'orocms/js/app/grapesjs/modules/style-manager-module';

const exposeStyles = html => {
    const domParser = new DOMParser();
    const body = domParser.parseFromString(html, 'text/html').body;

    return [...body.querySelectorAll('style')].reduce((acc, style) => {
        acc += style.innerHTML;
        return acc;
    }, '');
};

export default GrapesJS.plugins.add('grapesjs-code-mode', (editor, {editorView} = {}) => {
    const state = editorView.getState();
    const commandId = 'toggle-code-mode';
    const {Panels, Commands} = editor;

    Commands.add(commandId, {
        run() {
            state.set('codeMode', true);
        },
        stop(editor, sender, {prevent = false} = {}) {
            if (prevent) {
                return false;
            }

            const confirm = new Modal({
                autoRender: true,
                className: 'modal oro-modal-danger',
                title: __('oro.cms.wysiwyg.external_markup_mode.confirmation.title'),
                content: __('oro.cms.wysiwyg.external_markup_mode.confirmation.desc')
            });

            confirm.on('ok', () => state.set('codeMode', false));
            confirm.on('close', () => {
                if (!state.get('codeMode')) {
                    return;
                }

                const button = Panels.getButton('options', 'enable-code-mode');
                button.set('active', true, {
                    silent: true
                });
                button.trigger('checkActive');
            });

            confirm.open();

            return false;
        }
    });

    Panels.addButton('options', {
        id: 'enable-code-mode',
        className: 'fa fa-code',
        attributes: {
            title: 'External Markup Mode'
        },
        context: 'enable-code-mode',
        active: state.get('codeMode'),
        command: commandId
    });

    const originMethods = pick(editor, ['getIsolatedCss', 'getCss']);

    const originGetPureStyle = editor.getPureStyle;
    const originSetComponents = editor.setComponents;

    editor.getPureStyle = css => {
        if (typeof css === 'string') {
            editor.storeProtectedCss = editor.getUnIsolatedCssFromString(css);
        }
        return originGetPureStyle(css);
    };

    editor.setComponents = (components, {fromImport, ...rest} = {}) => {
        if (fromImport && typeof components === 'string') {
            editor.storeProtectedCss = exposeStyles(components);
        }

        return originSetComponents(components, rest);
    };

    const onLoad = () => {
        const state = editor.getState();

        if (state.get('codeMode')) {
            enableCodeMode();
        }
    };

    const enableCodeMode = () => {
        editor.StyleManager.destroy();

        editor.getIsolatedCss = () => {
            return editor.getIsolatedCssFromString(editor.storeProtectedCss);
        };

        editor.getCss = () => {
            return editor.storeProtectedCss;
        };
    };

    const disableCodeMode = () => {
        const styleManager = editor.StyleManager.render();
        editor.StyleManager.getSectors().reset(styleManagerModule);
        editor.Panels.getPanel('views-container').view
            .$el.find(':scope > div:nth-child(2) > div:first-child').append(styleManager);
        Object.assign(editor, originMethods);
    };

    editor.once('load', onLoad);
    state.on('change:codeMode', (state, codeMode) => {
        if (codeMode) {
            enableCodeMode();
        } else {
            disableCodeMode();
        }
    });

    editor.on('destroy', () => {
        state.off('change:codeMode');

        if (Commands.isActive(commandId)) {
            Commands.stop(commandId, {prevent: true});
        }
    });
});
