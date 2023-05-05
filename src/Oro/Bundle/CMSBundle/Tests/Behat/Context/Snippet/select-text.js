// eslint-disable-next-line no-unused-vars
function selectText(foundText, position) {
    const text = document.querySelector('[contenteditable="true"]');

    const range = new Range();
    const selection = document.getSelection();

    const filterRanges = node => {
        [...node.childNodes].forEach(child => {
            if (child.nodeType === 1) {
                return filterRanges(child);
            }

            if (child.nodeType === 3 && child.nodeValue.indexOf(foundText) !== -1) {
                const posStart = child.nodeValue.indexOf(foundText);
                const posEnd = posStart + foundText.length;

                if (position === 'before') {
                    range.setStart(child, posStart);
                } else if (position === 'after') {
                    range.setStart(child, posEnd);
                } else {
                    range.setStart(child, posStart);
                    range.setEnd(child, posEnd);
                }

                return;
            }
        });
    };

    if (foundText) {
        filterRanges(text);
    } else {
        range.selectNodeContents(text);
    }

    selection.removeAllRanges();
    selection.addRange(range);
    text.focus();
    text.dispatchEvent(new Event('mouseup'));
}
