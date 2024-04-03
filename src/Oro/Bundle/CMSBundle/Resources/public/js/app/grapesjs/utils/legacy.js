/**
 * Fetch breakpoints from old themes version <=5.1
 *
 * @param {document|Element} context
 */
export function getLegacyBreakpoints(context) {
    const breakpoint = {};
    const regexp = /(--[\w-]*:)/g;
    const regexpVal = /:\s?[\w\d-(): ]*/g;

    if (!context) {
        context = document.head;
    }

    let content = window.getComputedStyle(context, ':before').getPropertyValue('content');

    if (content === 'none') {
        return;
    }

    content = content.split('|');
    content.forEach((value, i) => {
        const name = value.match(regexp);
        const varVal = value.match(regexpVal);
        if (name && varVal) {
            breakpoint[name[0].slice(0, -1).replace('--breakpoints-', '')] = varVal[0].substr(1).trim();
        }
    });

    return breakpoint;
}
