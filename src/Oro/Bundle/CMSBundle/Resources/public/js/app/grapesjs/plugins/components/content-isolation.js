import $ from 'jquery';
import {uniqueId, each} from 'underscore';
import CONSTANTS from 'orocms/js/app/grapesjs/constants';

const ISOLATION_SCOPE = `${CONSTANTS.ISOLATION_PREFIX}-`;

const componentHtmlIdRegexp = new RegExp(`(<div id="${ISOLATION_SCOPE}([\\w]*))`, 'g');
// @deprecated
const componentCssIdRegexp = new RegExp(`(\\[id="${ISOLATION_SCOPE}([\\w]*)"\\])`, 'g');
const cssSelectorRegexp = /^(?![\@\}\{].*$).*(?:[\[\.\#\,\w\|\-\:\^\+\*\~\$\>\s]+)[\#\.\w\:\-\s\*\(\)\[\]\=\"]+\s?(?=\{)/gm;
const cssChildrenScopeRegexp = new RegExp(`#${ISOLATION_SCOPE}[\\w]*\\s?`, 'g');
const FORBIDDEN_ATTR = ['draggable', 'data-gjs[-\\w]+'];

const ROOT_ATTR_REGEXP = /\[id\*\=\"isolation\"\]/gm;
const SCOPE_ATTR_REGEXP = /\[id\*\=\"scope\"\]/gm;

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

export const separateContent = html => {
    const domParser = new DOMParser();
    const body = domParser.parseFromString(html, 'text/html').body;

    const css = [...body.querySelectorAll('style')].reduce((acc, style) => {
        acc += style.innerHTML;
        style.remove();
        return acc;
    }, '');

    return {
        html: body.innerHTML,
        css
    };
};

export const escapeWrapper = html => {
    if (hasIsolation(html)) {
        html = $(html).html();
        html = escapeWrapper(html);
    }

    return html;
};

export const escapeCss = css => {
    return css
        .replace(ROOT_ATTR_REGEXP, ':root')
        .replace(SCOPE_ATTR_REGEXP, ':scope')
        .replace(cssChildrenScopeRegexp, '')
        .replace(componentCssIdRegexp, '');
};

export const getWrapperAttrs = html => {
    const attrs = {};
    if (hasIsolation(html)) {
        const $wrapper = $(stripRestrictedAttrs(html));
        each($wrapper[0].attributes, attr => attrs[attr.name] = $wrapper.attr(attr.name));
    }
    delete attrs.id;
    return attrs;
};

export const stripRestrictedAttrs = html => {
    FORBIDDEN_ATTR.forEach(attr => {
        html = html.replace(new RegExp(`([\\s])?${attr}((=\\"([^"]*)\\")|(=\\'([^']*)\\'))?`, 'gm'), '');
    });

    return html;
};

function randomId(length = 20) {
    return uniqueId(
        [...Array(length)].map(i => (~~(Math.random() * 36)).toString(36)).join('')
    );
}

class ContentIsolation {
    constructor({scopeId = randomId()} = {}) {
        this.scopeId = ISOLATION_SCOPE + scopeId;
        this.randomId = scopeId;
    }

    beforeIsolateHook(css) {
        return css.replace(/\{|\}/gm, '\n$&\n');
    }

    afterIsolateHook(css) {
        return css.trim().replace(/\n/gm, '');
    }

    isolateCss(css) {
        const spacesBefore = css.match(/^\s+/g);
        css = this.beforeIsolateHook(css);

        css = css.replace(cssSelectorRegexp, substr => {
            if (/^\s+@[\w\-]+/g.test(substr)) {
                return substr;
            }

            return substr.split(',').map(selector => {
                if (/(\:scope)/.test(selector)) {
                    return selector.trim().replace(/(\:scope)/g, ` #${this.scopeId}[id*="scope"]`);
                }
                if (/(\:root)/.test(selector)) {
                    return selector.trim().replace(/(\:root)/g, ` #${this.scopeId}[id*="isolation"]`);
                }
                return ` #${this.scopeId}${(selector.trim().indexOf(':') === 0 ? '' : ' ')}${selector.trim()}`;
            }).join(',');
        });

        return (spacesBefore ? spacesBefore[0] : '') + this.afterIsolateHook(css);
    }

    escapeCssIsolation(css) {
        return escapeCss(css);
    }

    isolateHtml(html, wrapperClasses = '') {
        const root = document.createElement('div');

        root.id = this.scopeId;
        root.innerHTML = html;

        if (wrapperClasses) {
            root.className = wrapperClasses;
        }

        return root.outerHTML;
    }
}

export default ContentIsolation;
