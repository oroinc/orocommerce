import simple from './simple';
import link from './link';
import formatBlock from './format-block';
import unorderedList from './unordered-list';
import orderedList from './ordered-list';
import indent from './indent';
import outdent from './outdent';
import wrap from './wrap';

export default [
    ...simple,
    orderedList,
    unorderedList,
    link,
    formatBlock,
    outdent,
    indent,
    wrap
];
