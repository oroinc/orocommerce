import {unescape} from 'underscore';

const REGEXP_TWIG_TAGS_ESC = /([\{|\%|\#]{2})([\w\W]+)([\%|\}|\#]{2})/gi;

export const unescapeTwigExpression = html => {
    return html.replace(REGEXP_TWIG_TAGS_ESC, match => unescape(match).replace(/&#039;/gi, `'`));
};
