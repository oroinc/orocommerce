import GrapesJS from 'grapesjs';
import {uniqueId} from 'underscore';
import $ from 'jquery';

const componentCssIdRegexp = /(\[id="isolation-scope-([\w]*)"\])/g;
const componentHtmlIdRegexp = /(<div id="isolation-scope-([\w]*))/g;
const cssSelectorRegexp = /(?:[\.\#])[\#\.\w\:\-\s\(\)\[\]\=\"]+\s?(?=\{)/g;

export const escapeWrapper = html => {
    if (componentHtmlIdRegexp.test(html)) {
        html = $(html).html();
        html = escapeWrapper(html);
    }

    return html;
};

function randomId(length = 20) {
    return uniqueId(
        [...Array(length)].map(i => (~~(Math.random() * 36)).toString(36)).join('')
    );
}

export default GrapesJS.plugins.add('grapesjs-style-isolation', (editor, options) => {
    const uniqId = 'id="isolation-scope-' + randomId() + '"';

    function removeCSSContainerId(cssText) {
        return cssText.replace(componentCssIdRegexp, '');
    }

    editor.getIsolatedHtml = content => {
        let html = escapeWrapper(editor.getHtml());
        content ? html = content : html;
        html = !html ? html : '<div ' + uniqId + '>' + html + '</div>';
        return html;
    };

    editor.getIsolatedCss = () => {
        let css = removeCSSContainerId(editor.getCss());

        css = css.replace(cssSelectorRegexp, '[' + uniqId + '] $&');
        return css;
    };

    editor.setIsolatedHtml = html => escapeWrapper(html);

    editor.setIsolatedStyle = (css = '') => {
        editor.setStyle(removeCSSContainerId(css));
    };
});
