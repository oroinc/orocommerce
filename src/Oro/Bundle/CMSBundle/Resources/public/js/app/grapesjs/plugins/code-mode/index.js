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

                const button = Panels.getButton('settings', 'enable-code-mode');

                button.set('active', true, {
                    silent: true
                });
                button.trigger('checkActive');
            });

            confirm.open();

            return false;
        }
    });

    Panels.addButton('settings', {
        id: 'enable-code-mode',
        className: 'gjs-pn-btn--switch',
        attributes: {
            id: 'enable-code-mode',
            title: __('oro.cms.wysiwyg.external_markup_mode.label')
        },
        data: {
            label: __('oro.cms.wysiwyg.external_markup_mode.label'),
            info: __('oro.cms.wysiwyg.external_markup_mode.info')
        },
        context: 'enable-code-mode',
        active: state.get('codeMode'),
        command: commandId
    });

    const originMethods = pick(editor, ['getIsolatedCss', 'getCss']);

    const originGetPureStyle = editor.getPureStyle;
    const originGetPureStyleString = editor.getPureStyleString;
    const originSetComponents = editor.setComponents;

    editor.getPureStyle = css => {
        if (typeof css === 'string') {
            editor.storeProtectedCss = editor.getUnIsolatedCssFromString(css);
        }
        return originGetPureStyle.call(editor, css);
    };

    editor.getPureStyleString = css => {
        if (typeof css === 'string') {
            editor.storeProtectedCss = editor.getUnIsolatedCssFromString(css);
        }
        return originGetPureStyleString.call(editor, css);
    };

    editor.setComponents = (components, {fromImport, ...rest} = {}) => {
        if (fromImport && typeof components === 'string') {
            editor.storeProtectedCss = exposeStyles(components);
        }

        return originSetComponents.call(editor, components, rest);
    };

    const onLoad = () => {
        const state = editor.getState();

        if (state.get('codeMode')) {
            enableCodeMode();
            toggleMessage();
        }
    };

    /**
     * Toggling to show message while Style Manager is disabled
     * Need for notice user, when External Markup Mode is enabled
     *
     * @param {boolean} show
     */
    const toggleMessage = (show = true) => {
        const $panelEl = editor.Panels.getPanel('views-container').view.$el;

        if (!$panelEl.length) {
            return;
        }

        const message = $panelEl.find('[data-role="code-mode-sm-message"]');

        if (show) {
            !message.length && $panelEl.find(':scope > div:nth-child(2) > div:first-child').append(
                `<div class="alert alert-danger" data-role="code-mode-sm-message">
                        ${__('oro.cms.wysiwyg.external_markup_mode.message')}
                    </div>`
            );
        } else {
            message.length && $panelEl.find('[data-role="code-mode-sm-message"]').remove();
        }
    };

    const enableCodeMode = () => {
        editor.storeProtectedCss = editor.getCss();

        editor.StyleManager.getSectors().reset();

        editor.getIsolatedCss = () => {
            return editor.getIsolatedCssFromString(editor.storeProtectedCss);
        };

        editor.getCss = () => {
            return editor.storeProtectedCss;
        };

        editor.trigger('code-mode:update', {
            enabled: true
        });
    };

    const disableCodeMode = () => {
        editor.StyleManager.getSectors().reset(styleManagerModule);
        Object.assign(editor, originMethods);

        editor.trigger('code-mode:update', {
            enabled: false
        });
    };

    editor.once('load', onLoad);
    state.on('change:codeMode', (state, codeMode) => {
        editor.getSelectedAll().forEach(selected => editor.selectRemove(selected));

        if (codeMode) {
            enableCodeMode();
        } else {
            disableCodeMode();
        }

        toggleMessage(codeMode);
    });

    editor.on('destroy', () => {
        state.off('change:codeMode');

        if (Commands.isActive(commandId)) {
            Commands.stop(commandId, {prevent: true});
        }
    });
});
