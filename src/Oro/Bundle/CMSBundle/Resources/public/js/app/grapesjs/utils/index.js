import _ from 'underscore';

const REGEXP_TWIG_TAGS_ESC = /([\{|\%|\#]{2})([\w\W]+)([\%|\}|\#]{2})/gi;
const TWIG_SAFE_CSS = /(\{)(\#|\%|\{)/gi;

export const unescapeTwigExpression = html => {
    return html.replace(REGEXP_TWIG_TAGS_ESC, match => _.unescape(match).replace(/&#039;/gi, `'`));
};

export const twigSafeCssFilter = css => {
    return css.replace(TWIG_SAFE_CSS, '$1 $2');
};
