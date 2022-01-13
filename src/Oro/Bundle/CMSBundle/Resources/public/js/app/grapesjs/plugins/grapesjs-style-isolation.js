import GrapesJS from 'grapesjs';
import $ from 'jquery';
import ContentIsolation, {escapeWrapper, stripRestrictedAttrs} from './components/content-isolation';

export default GrapesJS.plugins.add('grapesjs-style-isolation', (editor, options) => {
    const contentIsolation = new ContentIsolation();

    editor.getIsolatedHtml = content => {
        const wrapper = editor.getWrapper();
        const wrapperClasses = wrapper.getClasses().join(' ');
        let html = stripRestrictedAttrs(escapeWrapper(editor.getHtml()), editor.getAllowedConfig());

        if (content) {
            html = content;
        }

        if (wrapperClasses.length || wrapper.styleToString().length || html.length) {
            html = contentIsolation.isolateHtml(html, wrapperClasses);
        }

        return html;
    };

    editor.getIsolatedCss = () => {
        const wrapperCss = editor.getWrapper().styleToString();
        let css = '';

        if (wrapperCss.length) {
            css += `#${contentIsolation.scopeId}{${wrapperCss}}`;
        }

        css += editor.CssComposer.getAll().reduce((acc, rule) => {
            // Do not remove space in replace phrase
            acc += contentIsolation.isolateCss(rule.toCSS());
            return acc;
        }, '');

        return css;
    };

    editor.setIsolatedHtml = html => escapeWrapper(html);

    editor.getPureStyle = (css = '') => {
        if (!css.length) {
            return '';
        }

        css = contentIsolation.escapeCssIsolation(css);

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
