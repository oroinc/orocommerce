import {uniqueId, each} from 'underscore';
import CONSTANTS from 'orocms/js/app/grapesjs/constants';
import {unescapeTwigExpression} from '../../utils';

const ISOLATION_SCOPE = `${CONSTANTS.ISOLATION_PREFIX}-`;

const componentHtmlIdRegexp = new RegExp(`(<div id="${ISOLATION_SCOPE}([\\w]*))`, 'g');
const componentCssIdRegexp = new RegExp(`(\\[id="${ISOLATION_SCOPE}([\\w]*)"\\])`, 'g');
const cssSelectorRegexp = /^(?![\@\}\{].*$).*(?:[\[\.\#\,\w\|\-\:\^\+\*\~\$\>\s]+)[\#\.\w\:\-\s\*\(\)\[\]\=\"]+\s?(?=\{)/gm;
const cssChildrenScopeRegexp = new RegExp(`#${ISOLATION_SCOPE}[\\w]*\\s?`, 'g');
const FORBIDDEN_ATTR = ['draggable', 'data-gjs[-\\w]+'];

const ROOT_ATTR_REGEXP = /\[id\*\=\"isolation\"\]/gm;
const SCOPE_ATTR_REGEXP = /\[id\*\=\"scope\"\]/gm;
const WRAPPER_REGEXP = /#isolation-scope-[\\w]*\\s?\.cms\-wrapper/gm;

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

const convertHtmlStringToNodes = html => {
    const domParser = new DOMParser();
    return domParser.parseFromString(html, 'text/html').body;
};

export const separateContent = html => {
    const body = convertHtmlStringToNodes(html);

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
        const body = convertHtmlStringToNodes(html);
        html = body.firstChild.innerHTML;
        html = escapeWrapper(html);
    }

    return html;
};

export const escapeCss = css => {
    return css
        .replace(ROOT_ATTR_REGEXP, ':root')
        .replace(SCOPE_ATTR_REGEXP, ':scope')
        .replace(WRAPPER_REGEXP, '.cms-wrapper')
        .replace(cssChildrenScopeRegexp, '')
        .replace(componentCssIdRegexp, '');
};

export const getWrapperAttrs = html => {
    const attrs = {};
    if (hasIsolation(html)) {
        const body = convertHtmlStringToNodes(stripRestrictedAttrs(html));
        each(body.firstChild.attributes, attr => attrs[attr.name] = body.firstChild.getAttribute(attr.name));
    }
    delete attrs.id;
    if (attrs.class) {
        attrs.class = attrs.class.split(' ').filter(className => className !== 'wrapper').join(' ');
    }
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
                if (/(\.cms\-wrapper)/.test(selector)) {
                    return selector.trim().replace(/(\.cms\-wrapper)/g, ` #${this.scopeId}.cms-wrapper`);
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
        const classes = wrapperClasses ? ` class="${wrapperClasses}"` : '';
        return `<div id="${this.scopeId}"${classes}>${unescapeTwigExpression(html)}</div>`;
    }
}

export default ContentIsolation;
