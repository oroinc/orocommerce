import ListMixin from 'orocms/js/app/grapesjs/plugins/components/rte/utils/list-mixins';
import fixture from 'text-loader!../../../fixtures/document-fixture.html';

describe('orocms/js/app/grapesjs/plugins/components/rte/utils/list-mixins', () => {
    let ulListMixin;
    let olListMixin;
    let rte;
    const editor = {
        trigger() {}
    };

    beforeEach(() => {
        window.setFixtures(fixture);
        document.querySelector('.container').setAttribute('contenteditable', true);
        rte = {
            doc: document,
            selection() {
                return document.getSelection();
            },
            exec(name) {
                return document.execCommand(name);
            },
            el: document.querySelector('.container')
        };
        ulListMixin = new ListMixin('UL', 'OL');
        olListMixin = new ListMixin('OL', 'UL');
    });

    afterEach(() => {
        ulListMixin = null;
        olListMixin = null;
    });

    it('check "mergeLists" middle', () => {
        const merged2 = document.getElementById('merged-2');

        ulListMixin.mergeLists(merged2);

        expect(merged2.outerHTML).toEqual(
            // eslint-disable-next-line
            '<ul id="merged-3" data-test-2="test-2" data-test-1="test-1" data-test-3="test-3"><li>List item 1</li><li>List item 2</li><li>List item 3</li><li>List item 4</li><li>List item 5</li></ul>'
        );
    });

    it('check "mergeLists"', () => {
        const merged1 = document.getElementById('merged-1');
        const merged3 = document.getElementById('merged-3');

        ulListMixin.mergeLists(merged1);

        expect(merged1.outerHTML).toEqual(
            // eslint-disable-next-line
            '<ul id="merged-2" data-test-1="test-1" data-test-2="test-2"><li>List item 1</li><li>List item 2</li><li>List item 3</li></ul>'
        );

        ulListMixin.mergeLists(merged3);
        expect(merged3.outerHTML).toEqual(
            // eslint-disable-next-line
            '<ul id="merged-2" data-test-3="test-3" data-test-1="test-1" data-test-2="test-2"><li>List item 1</li><li>List item 2</li><li>List item 3</li><li>List item 4</li><li>List item 5</li></ul>'
        );
    });

    it('check "insertNodeToList"', () => {
        const heading = document.querySelector('.heading');
        const paragraph = document.querySelector('.paragraph');
        const paragraph1 = document.querySelector('.paragraph-1');

        ulListMixin.insertNodeToList(heading);
        olListMixin.insertNodeToList(paragraph);
        olListMixin.insertNodeToList(paragraph1);

        expect(heading.closest('ul').outerHTML).toEqual(`<ul><li><h1 class="heading">Heading 1</h1></li></ul>`);
        expect(paragraph.closest('ol').outerHTML).toEqual(`<ol><li><p class="paragraph">Lorem ipsum</p></li></ol>`);
        expect(paragraph1.closest('ol').outerHTML).toEqual(
            `<ol><li><p class="paragraph-1">Lorem ipsum<br>Lorem ipsum<br>Lorem ipsum<br>Lorem ipsum<br></p></li></ol>`
        );
    });

    it('check "insertNodesToList"', () => {
        const nodes = document.querySelector('.box').childNodes;

        ulListMixin.insertNodesToList([...nodes], document.querySelector('.box'));

        expect(document.querySelector('.box > ul').outerHTML).toEqual(
            // eslint-disable-next-line
            `<ul><li>Text </li><li><h2>Heading 2</h2></li><li> text text</li><li><h3>Heading 3</h3></li><li><p>Paragraph</p></li></ul>`
        );
    });

    it('check "processList"', () => {
        const range = new Range();
        const lines = document.querySelector('.lines');
        range.selectNodeContents(lines.childNodes[2]);
        document.getSelection().removeAllRanges();
        document.getSelection().addRange(range);

        ulListMixin.processList(rte, editor);

        expect(document.querySelector('.lines').outerHTML).toEqual(`<div class="lines">
        Text line 1<br><ul><li>
        Text line 2</li></ul>
        Text line 3<br>
    </div>`);

        ulListMixin.processList(rte, editor);
        expect(document.querySelector('.lines').outerHTML).toEqual(`<div class="lines">
        Text line 1<br>
        Text line 2<br>
        Text line 3<br>
    </div>`);
    });

    it('check "processList" nested formatting', () => {
        const range = new Range();
        const nestedFormat = document.querySelector('.with-nested-format i');
        range.selectNodeContents(nestedFormat);
        document.getSelection().removeAllRanges();
        document.getSelection().addRange(range);

        ulListMixin.processList(rte, editor);
        expect(document.querySelector('.with-nested-format').outerHTML).toEqual(
            // eslint-disable-next-line
            `<div class="with-nested-format"><ul><li><b><u><i>Test text 1</i></u></b></li><li><b><u><i>Test text 2</i></u></b></li><li><b><u><i>Test text 3</i></u></b></li></ul></div>`);
    });

    it('check "processSubList" single', () => {
        const range = new Range();
        const listItem = document.querySelector('.single-ol-list li:nth-child(2)');
        range.selectNodeContents(listItem.childNodes[0]);
        document.getSelection().removeAllRanges();
        document.getSelection().addRange(range);

        olListMixin.processSubList(rte, editor);

        expect(document.querySelector('.single-ol-list').outerHTML).toEqual(
            // eslint-disable-next-line
            `<ol class="single-ol-list"><li>List item 1<ol><li>List item 2</li></ol></li><li>List item 3</li></ol>`
        );

        olListMixin.processSubList(rte, editor, true);

        expect(document.querySelector('.single-ol-list').outerHTML).toEqual(
            // eslint-disable-next-line
            `<ol class="single-ol-list"><li>List item 1</li><li>List item 2</li><li>List item 3</li></ol>`
        );
    });

    it('check "processSubList" multiselect', () => {
        const range = new Range();
        const listItem1 = document.querySelector('.single-ul-list li:nth-child(2)');
        const listItem2 = document.querySelector('.single-ul-list li:nth-child(4)');
        range.setStart(listItem1.firstChild, 0);
        range.setEnd(listItem2.firstChild, listItem2.firstChild.length);
        document.getSelection().removeAllRanges();
        document.getSelection().addRange(range);

        ulListMixin.processSubList(rte, editor);
        expect(document.querySelector('.single-ul-list').outerHTML).toEqual(
            // eslint-disable-next-line
            `<ul class="single-ul-list"><li>List item 1<ul><li>List item 2</li><li>List item 3</li><li>List item 4</li></ul></li><li>List item 5</li></ul>`
        );

        ulListMixin.processSubList(rte, editor);
        expect(document.querySelector('.single-ul-list').outerHTML).toEqual(
            // eslint-disable-next-line
            `<ul class="single-ul-list"><li>List item 1<ul><li class="list-style-none"><ul><li>List item 2</li><li>List item 3</li><li>List item 4</li></ul></li></ul></li><li>List item 5</li></ul>`
        );

        ulListMixin.processSubList(rte, editor, true);
        expect(document.querySelector('.single-ul-list').outerHTML).toEqual(
            // eslint-disable-next-line
            `<ul class="single-ul-list"><li>List item 1<ul><li>List item 2</li><li>List item 3</li><li>List item 4</li></ul></li><li>List item 5</li></ul>`
        );

        ulListMixin.processSubList(rte, editor, true);
        expect(document.querySelector('.single-ul-list').outerHTML).toEqual(
            // eslint-disable-next-line
            `<ul class="single-ul-list"><li>List item 1</li><li>List item 2</li><li>List item 3</li><li>List item 4</li><li>List item 5</li></ul>`
        );

        ulListMixin.processSubList(rte, editor, true);
        expect(document.querySelector('.single-ul-list').outerHTML).toEqual(
            // eslint-disable-next-line
            `<ul class="single-ul-list"><li>List item 1</li><li>List item 2</li><li>List item 3</li><li>List item 4</li><li>List item 5</li></ul>`
        );
    });
});
