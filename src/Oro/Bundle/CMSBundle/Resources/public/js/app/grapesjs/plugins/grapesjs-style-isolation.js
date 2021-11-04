import GrapesJS from 'grapesjs';
import {uniqueId, each} from 'underscore';
import $ from 'jquery';

import CONSTANTS from 'orocms/js/app/grapesjs/constants';
const ISOLATION_SCOPE = `${CONSTANTS.ISOLATION_PREFIX}-`;

const componentHtmlIdRegexp = new RegExp(`(<div id="${ISOLATION_SCOPE}([\\w]*))`, 'g');
// @deprecated
const componentCssIdRegexp = new RegExp(`(\\[id="${ISOLATION_SCOPE}([\\w]*)"\\])`, 'g');
const cssSelectorRegexp = /(?:[\.\#])[\#\.\w\:\-\s\(\)\[\]\=\"]+\s?(?=\{)/g;
const cssWrapperScopeRegexp = new RegExp(`^#${ISOLATION_SCOPE}[\\w]+\\{`);
const cssChildrenScopeRegexp = new RegExp(`#${ISOLATION_SCOPE}[\\w]*\\s+`, 'g');

const FORBIDDEN_ATTR = ['draggable', 'data-gjs[-\\w]+'];

/**
 * Test regexp
 * Prevent shift regexp index
 * @param html
 * @returns {boolean}
 */
const hasIsolation = html => {
    componentHtmlIdRegexp.lastIndex = 0;
    return componentHtmlIdRegexp.test(html);
};

export const escapeWrapper = html => {
    if (hasIsolation(html)) {
        html = $(html).html();
        html = escapeWrapper(html);
    }

    return html;
};

export const escapeCss = css => css
    .replace(cssWrapperScopeRegexp, '#wrapper{')
    .replace(componentCssIdRegexp, '')
    .replace(cssChildrenScopeRegexp, '');

export const getWrapperAttrs = html => {
    const attrs = {};
    if (hasIsolation(html)) {
        const $wrapper = $(html);
        each($wrapper[0].attributes, attr => attrs[attr.name] = $wrapper.attr(attr.name));
    }
    delete attrs.id;
    return attrs;
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
    const scopeId = ISOLATION_SCOPE + randomId();

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
        let css = '';

        if (wrapperCss.length) {
            css += `#${scopeId}{${wrapperCss}}`;
        }

        css += editor.CssComposer.getAll().reduce((acc, rule) => {
            // Do not remove space in replace phrase
            acc += rule.toCSS().replace(cssSelectorRegexp, ` #${scopeId} $&`);
            return acc;
        }, '');

        return css;
    };

    editor.setIsolatedHtml = html => escapeWrapper(html);

    editor.getPureStyle = (css = '') => {
        if (!css.length) {
            return '';
        }

        css = css
            .replace(cssWrapperScopeRegexp, '#wrapper{')
            .replace(componentCssIdRegexp, '')
            .replace(cssChildrenScopeRegexp, '');

        const errors = editor.CodeValidator.validate(`<style>${css}</style>`);

        if (errors.length) {
            console.error(`Invalid styles cannot apply in editor, check source code "
                ${errors.map((({message}) => message)).join('\n')}
            "`);
            return false;
        }

        const _res = editor.Parser.parseCss(css).reduce((acc, rule, index, collection) => {
            const {state = '', atRuleType = '', mediaText = '', selectorsAdd = ''} = rule;
            const key = rule.selectors.join('') + state + atRuleType + mediaText + selectorsAdd;

            acc[key] = $.extend(true, acc[key] || {}, rule);
            return acc;
        }, {});

        return Object.values(_res);
    };
});
