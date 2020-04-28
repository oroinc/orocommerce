import GrapesJS from 'grapesjs';
import {uniqueId} from 'underscore';
import $ from 'jquery';

const componentHtmlIdRegexp = /(<div id="isolation-scope-([\w]*))/g;
// @deprecated
const componentCssIdRegexp = /(\[id="isolation-scope-([\w]*)"\])/g;
const cssSelectorRegexp = /(?:[\.\#])[\#\.\w\:\-\s\(\)\[\]\=\"]+\s?(?=\{)/g;
const cssWrapperScopeRegexp = /^#isolation-scope-[\w]+\{/;
const cssChildrenScopeRegexp = /#isolation-scope-[\w]*\s+/g;

const FORBIDDEN_ATTR = ['draggable', 'data-gjs[-\\w]+'];

export const escapeWrapper = html => {
    if (componentHtmlIdRegexp.test(html)) {
        html = $(html).html();
        html = escapeWrapper(html);
    }

    return html;
};

export const stripRestrictedAttrs = html => {
    FORBIDDEN_ATTR.forEach(attr => {
        html = html.replace(new RegExp(`([\\s])${attr}((=\"([^"]*)\")|(=\'([^']*)\'))?`, 'g'), '');
    });

    return html;
};

function randomId(length = 20) {
    return uniqueId(
        [...Array(length)].map(i => (~~(Math.random() * 36)).toString(36)).join('')
    );
}

export default GrapesJS.plugins.add('grapesjs-style-isolation', (editor, options) => {
    const scopeId = 'isolation-scope-' + randomId();

    editor.getIsolatedHtml = content => {
        const wrapper = editor.getWrapper();
        const wrapperClasses = wrapper.getClasses().join(' ');
        let html = stripRestrictedAttrs(escapeWrapper(editor.getHtml()), editor.getAllowedConfig());

        if (content) {
            html = content;
        }

        if (wrapperClasses.length || wrapper.styleToString().length || html.length) {
            const root = document.createElement('div');

            root.id = scopeId;
            root.innerHTML = html;

            if (wrapperClasses.length) {
                root.className = wrapperClasses;
            }

            html = root.outerHTML;
        }

        return html;
    };

    editor.getIsolatedCss = () => {
        const wrapperCss = editor.getWrapper().styleToString();
        const cssc = editor.CssComposer;
        const components = editor.DomComponents.getComponent().get('components');
        let css = '';

        if (wrapperCss.length) {
            css += `#${scopeId}{${wrapperCss}}`;
        }

        if (components.length) {
            let childrenCss = '';

            components.each(component => {
                const componentCss = editor.CodeManager.getCode(component, 'css', {cssc});

                if (componentCss.length) {
                    // Do not remove space in replace phrase
                    childrenCss += componentCss.replace(cssSelectorRegexp, ` #${scopeId} $&`);
                }
            });

            css += childrenCss;
        }

        return css;
    };

    editor.setIsolatedHtml = html => escapeWrapper(html);

    editor.getPureStyle = (css = '') => {
        if (!css.length) {
            return '';
        }

        return css
            .replace(cssWrapperScopeRegexp, '#wrapper{')
            .replace(componentCssIdRegexp, '')
            .replace(cssChildrenScopeRegexp, '');
    };
});
