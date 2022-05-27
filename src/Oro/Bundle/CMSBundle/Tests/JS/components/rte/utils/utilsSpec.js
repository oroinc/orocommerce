import * as utils from 'orocms/js/app/grapesjs/plugins/components/rte/utils/utils';
import fixture from 'text-loader!../../../fixtures/document-fixture.html';

describe('orocms/js/app/grapesjs/plugins/components/rte/utils/utils', () => {
    let nodesCollection;
    beforeEach(() => {
        window.setFixtures(fixture);
        nodesCollection = {
            div: document.createElement('DIV'),
            h1: document.createElement('H1'),
            p: document.createElement('P'),
            span: document.createElement('SPAN'),
            ol: document.createElement('OL'),
            ul: document.createElement('UL'),
            b: document.createElement('B'),
            sup: document.createElement('SUP'),
            strike: document.createElement('STRIKE'),
            text: document.createTextNode('Test'),
            wrapper: document.createElement('DIV')
        };

        nodesCollection.wrapper.classList.add('wrapper');
    });

    afterEach(() => {
        for (const node of Object.values(nodesCollection)) {
            node.remove();
        }
    });

    it('check "isBlockFormatted"', () => {
        expect(utils.isBlockFormatted(nodesCollection.h1)).toBeTruthy();
        expect(utils.isBlockFormatted(nodesCollection.p)).toBeTruthy();
        expect(utils.isBlockFormatted(nodesCollection.span)).toBeFalsy();
        expect(utils.isBlockFormatted(nodesCollection.text)).toBeFalsy();
    });

    it('check "isFormattedText"', () => {
        expect(utils.isFormattedText(nodesCollection.b)).toBeTruthy();
        expect(utils.isFormattedText(nodesCollection.strike)).toBeTruthy();
        expect(utils.isFormattedText(nodesCollection.span)).toBeFalsy();
        expect(utils.isFormattedText(nodesCollection.text)).toBeFalsy();
    });

    it('check "isContainLists"', () => {
        expect(utils.isContainLists(nodesCollection.ul)).toBeTruthy();
        expect(utils.isContainLists(nodesCollection.ol)).toBeTruthy();
        expect(utils.isContainLists(nodesCollection.span)).toBeFalsy();
        expect(utils.isContainLists(nodesCollection.text)).toBeFalsy();
    });

    it('check "surroundContent"', () => {
        nodesCollection.div.innerHTML = '<span class="span">Hello</span> <b>world</b><strike>!</strike>';
        nodesCollection.div.id = 'test-id';
        nodesCollection.div.classList = 'test-class';
        nodesCollection.h1.id = 'heading-id';

        utils.surroundContent(nodesCollection.div, nodesCollection.h1);

        expect(nodesCollection.div.outerHTML).toEqual(
            // eslint-disable-next-line
            '<div id="test-id" class="test-class"><h1 id="heading-id"><span class="span">Hello</span> <b>world</b><strike>!</strike></h1></div>'
        );
    });

    it('check "unwrap"', () => {
        nodesCollection.h1.innerHTML = '<span class="span">Hello</span> <b>world</b><strike>!</strike>';
        nodesCollection.h1.id = 'test-id';
        nodesCollection.h1.classList = 'test-class';
        nodesCollection.div.append(nodesCollection.h1);
        nodesCollection.wrapper.append(nodesCollection.div);

        utils.unwrap(nodesCollection.div);

        expect(nodesCollection.wrapper.outerHTML).toEqual(
            // eslint-disable-next-line
            '<div class="wrapper"><h1 id="test-id" class="test-class"><span class="span">Hello</span> <b>world</b><strike>!</strike></h1></div>'
        );
    });

    it('check "makeSurroundNode"', () => {
        const surround = utils.makeSurroundNode(document);

        nodesCollection.div.innerHTML = '<span class="span">Hello</span> <b>world</b><strike>!</strike>';
        nodesCollection.div.id = 'test-id';
        nodesCollection.div.classList = 'test-class';

        surround(nodesCollection.div, 'H1');

        expect(nodesCollection.div.outerHTML).toEqual(
            // eslint-disable-next-line
            '<div id="test-id" class="test-class"><h1><span class="span">Hello</span> <b>world</b><strike>!</strike></h1></div>'
        );
    });

    it('check "clearTextFormatting"', () => {
        nodesCollection.div.innerHTML = '<h1>Test content</h1>';

        utils.clearTextFormatting(nodesCollection.div);
        expect(nodesCollection.div.outerHTML).toEqual('<div>Test content</div>');

        nodesCollection.div.innerHTML = '<h1>Test <b>content</b></h1>';
        utils.clearTextFormatting(nodesCollection.div);
        expect(nodesCollection.div.outerHTML).toEqual('<div>Test <b>content</b></div>');
    });

    it('check "findTextFormattingInRange"', () => {
        const range = new Range();
        const box = document.querySelector('.box');
        range.setStart(box.firstChild, 0);
        range.setEnd(box.childNodes[2], box.childNodes[2].length);
        document.getSelection().addRange(range);

        expect(utils.findTextFormattingInRange(range)).toEqual(['h2']);

        range.setEnd(box.childNodes[3], box.childNodes[3].length);
        expect(utils.findTextFormattingInRange(range)).toEqual(['h2', 'h3']);

        range.setEnd(box.childNodes[4], box.childNodes[4].length);
        expect(utils.findTextFormattingInRange(range)).toEqual(['h2', 'h3', 'p']);
    });

    it('check "findClosestFormattingBlock"', () => {
        const node = document.querySelector('.formatted b');

        expect(utils.findClosestFormattingBlock(node).nodeType).toEqual(1);
        expect(utils.findClosestFormattingBlock(node).tagName).toEqual('H1');
    });

    it('check "findClosestListType"', () => {
        const nodeOl = document.querySelector('.single-ol-list li:nth-child(1)').firstChild;
        const nodeUl = document.querySelector('.single-ul-list li:nth-child(1)').firstChild;

        expect(utils.findClosestListType(nodeOl)).toEqual('ordered');
        expect(utils.findClosestListType(nodeUl)).toEqual('unordered');
    });

    it('check "getParentsUntil"', () => {
        const node = document.querySelector('.formatted b');

        expect(utils.getParentsUntil(node, document.querySelector('.formatted'))).toEqual([
            document.querySelector('.formatted b'),
            document.querySelector('.formatted span'),
            document.querySelector('.formatted h1')
        ]);
    });

    it('check "findParentTag"', () => {
        expect(utils.findParentTag(document.querySelector('.formatted b'), 'h1')).toEqual(
            document.querySelector('.formatted h1')
        );
        expect(utils.findParentTag(document.querySelector('.sublist p'), 'ul')).toEqual(
            document.querySelector('.list')
        );
        expect(utils.findParentTag(document.querySelector('.sublist p'), 'ul', true)).toEqual(
            document.querySelector('.sublist')
        );
    });

    it('check "changeTagName"', () => {
        expect(utils.changeTagName(document.querySelector('.formatted h1'), 'h3')).toEqual(
            document.querySelector('.formatted h3')
        );
        expect(utils.changeTagName(document.querySelector('.formatted h3'), 'div')).toEqual(
            document.querySelector('.formatted div')
        );

        expect(document.querySelector('.formatted').innerHTML).toEqual(`
        <div>Text
            <span>test
                <b>bold</b>
            </span>
        </div>
    `);
    });

    it('check "cloneAttrs"', () => {
        nodesCollection.h1.setAttribute('data-attr', 'attr-value');
        nodesCollection.h1.setAttribute('class', 'class-name');
        nodesCollection.h1.setAttribute('id', 'element-id');

        utils.cloneAttrs(nodesCollection.div, nodesCollection.h1);

        expect(nodesCollection.div.outerHTML).toEqual(
            '<div data-attr="attr-value" class="class-name" id="element-id"></div>'
        );
    });
});
