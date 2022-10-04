import formatBlock from 'orocms/js/app/grapesjs/plugins/components/rte/format-block';

const TEST_CASES = {
    [`Test <span>Selected</span> test`]: `<%s>Test <span>Selected</span> test</%s>`,
    [`<span data-type="text-style">Selected</span>`]: `<%s><span data-type="text-style">Selected</span></%s>`,
    [`Test <i><b>Selected</b></i> test`]: `<%s>Test <i><b>Selected</b></i> test</%s>`,
    [`Test Selected test`]: `<%s>Test Selected test</%s>`,
    [`Test <br> Selected test`]: `Test <br><%s> Selected test</%s>`,
    [`<u>Test</u> <b>Selected</b> <i>test</i>`]: `<%s><u>Test</u> <b>Selected</b> <i>test</i></%s>`,
    [`Selected`]: `<%s>Selected</%s>`,
    [`Selected<br> Test<br> Test`]: `<%s>Selected</%s><br> Test<br> Test`,
    [`<ul><li>Selected</li><li>Test</li></ul>`]: `<ul><li><%s>Selected</%s></li><li>Test</li></ul>`,
    [`<ul><li><b>Selected</b></li><li>Test</li></ul>`]: `<ul><li><%s><b>Selected</b></%s></li><li>Test</li></ul>`,
    [`<div>Test <span><b>Selected</b></span> Test</div>`]: `<div><%s>Test <span><b>Selected</b></span> Test</%s></div>`
};

const selectText = foundText => {
    const text = document.querySelector('[contenteditable="true"]');

    const selection = document.getSelection();
    selection.removeAllRanges();
    const filterRanges = node => {
        [...node.childNodes].forEach(child => {
            if (child.nodeType === 1) {
                return filterRanges(child);
            }

            if (child.nodeType === 3 && child.nodeValue.indexOf(foundText) !== -1) {
                const range = new Range();
                const posStart = child.nodeValue.indexOf(foundText);
                const posEnd = posStart + foundText.length;
                range.setStart(child, posStart);
                range.setEnd(child, posEnd);
                selection.addRange(range);
                return;
            }
        });
    };

    filterRanges(text);
    text.focus();
};

describe('orocms/js/app/grapesjs/plugins/components/rte/format-block', () => {
    let rte;
    let actionbar;
    let content;

    beforeEach(() => {
        window.setFixtures(`<div id="content" contenteditable="true"></div>`);
        content = document.querySelector('#content');
        actionbar = document.createElement('div');
        actionbar.innerHTML = formatBlock.icon;
        formatBlock.editor = {
            trigger() {}
        };
        rte = {
            doc: document,
            selection() {
                return document.getSelection();
            },
            exec(name, value) {
                return document.execCommand(name, value);
            },
            actionbar,
            el: document.querySelector('.container')
        };
    });

    afterEach(() => {
        document.getSelection().removeAllRanges();
        actionbar.remove();
    });

    for (const [source, expectResult] of Object.entries(TEST_CASES)) {
        // eslint-disable-next-line
        it(`test case "${source}" to format block`, () => {
            for (const param of ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']) {
                content.innerHTML = source;
                selectText('Selected');
                actionbar.querySelector('[name="tag"]').value = param;
                formatBlock.result(rte);

                expect(content.innerHTML).toEqual(expectResult.replace(/%s/g, param));
                document.getSelection().removeAllRanges();
            }
        });

        // eslint-disable-next-line
        it(`test case format block to "${source}"`, () => {
            for (const param of ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']) {
                content.innerHTML = expectResult.replace(/%s/g, param);
                selectText('Selected');
                actionbar.querySelector('[name="tag"]').value = 'normal';
                formatBlock.result(rte);

                expect(content.innerHTML).toEqual(source);
                document.getSelection().removeAllRanges();
            }
        });
    }
});
