import __ from 'orotranslation/js/translator';

function cssValidation(parameters) {
    const {cache, htmlFragment, editor, htmlStringLine, lineNumber} = parameters;
    if (!cache['styleTags']) {
        cache['styleTags'] = [...htmlFragment.querySelectorAll('style')];
    }

    if (!htmlStringLine.includes('<style>') || cache['styleTags'].length === 0) {
        return;
    }

    try {
        const css = cache['styleTags'][0].innerText;
        cache['styleTags'].shift();
        editor.em.get('Parser').parseCss(css);
    } catch (e) {
        if (!cache[htmlStringLine]) {
            cache[htmlStringLine] = e;
        }
    }

    if (cache[htmlStringLine]) {
        return {
            message: __('oro.htmlpurifier.messages.invalid_styles', {
                reason: cache[htmlStringLine].reason,
                column: cache[htmlStringLine].column
            }),
            shortMessage: __('oro.htmlpurifier.messages.invalid_styles_short', {
                reason: cache[htmlStringLine].reason
            }),
            lineNumber: lineNumber + cache[htmlStringLine].line - 1
        };
    }
}

export default cssValidation;
