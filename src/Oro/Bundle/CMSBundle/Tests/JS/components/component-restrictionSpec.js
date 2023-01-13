import grapesJS from 'grapesjs';
import $ from 'jquery';
import 'jasmine-jquery';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';

describe('orocms/js/app/grapesjs/plugins/components/component-restriction', () => {
    let componentRestriction;
    let editor;

    beforeEach(done => {
        window.setFixtures(html);
        editor = grapesJS.init({
            container: document.querySelector('.page-content-editor'),
            deviceManager: {
                devices: []
            }
        });
        editor.on('load', () => done());
    });

    afterEach(() => {
        editor.destroy();
    });

    describe('feature "ComponentRestriction"', () => {
        beforeEach(() => {
            componentRestriction = new ComponentRestriction(editor, {
                allowTags: [
                    '@[id|style|class]',
                    'table[cellspacing|cellpadding|border|align|width]',
                    'div[data-title|data-type|data-image]'
                ],
                allowedIframeDomains: [
                    'youtube.com/embed/',
                    'www.youtube.com/embed/',
                    'youtube-nocookie.com/embed/',
                    'www.youtube-nocookie.com/embed/',
                    'player.vimeo.com/video/',
                    'maps.google.com/maps'
                ]
            });
        });

        it('check is defined', () => {
            expect(componentRestriction).toBeDefined();
        });

        it('check prepare allow tags', () => {
            expect(componentRestriction.allowTags).toEqual([
                ['table', ['cellspacing', 'cellpadding', 'border', 'align', 'width', 'id', 'style', 'class']],
                ['div', ['data-title', 'data-type', 'data-image', 'id', 'style', 'class']]
            ]);
        });

        it('check get config', () => {
            expect(componentRestriction.getConfig('div')).toEqual(['div', [
                'data-title',
                'data-type',
                'data-image',
                'id',
                'style',
                'class'
            ]]);
            expect(componentRestriction.getConfig('table')).toEqual(['table', [
                'cellspacing',
                'cellpadding',
                'border',
                'align',
                'width',
                'id',
                'style',
                'class'
            ]]);
        });

        it('check get tags from element', () => {
            expect(componentRestriction.getTags($(
                `<div data-test="Test">
                <span data-action="test">Test</span>
            </div>`)[0])).toEqual([
                ['div', ['data-test']],
                ['span', ['data-action']]
            ]);

            expect(componentRestriction.getTags($(
                `<div data-test="Test">
                <div>Content</div>
                <span data-action="test">Test</span>
                <div data-image="Image">
                    <img src="#" alt="Alt">
                </div>
            </div>`)[0])).toEqual([
                ['div', ['data-test']],
                'div',
                ['span', ['data-action']],
                ['div', ['data-image']],
                ['img', ['src', 'alt']]
            ]);

            expect(componentRestriction.getTags($(
                `<div data-test="Test">
                <div>Content</div>
                <span data-action="test">Test</span>
                <div data-image="Image">
                    <img src="#" alt="Alt">
                </div>
            </div>`)[0], true)).toEqual([
                ['div', ['data-test'], '<div data-test="Test">'],
                'div',
                ['span', ['data-action'], '<span data-action="test">'],
                ['div', ['data-image'], '<div data-image="Image">'],
                ['img', ['src', 'alt'], '<img src="#" alt="Alt">']
            ]);
        });

        it('check domains allowed', () => {
            expect(componentRestriction.isAllowedDomain('http://youtube.com/embed/')).toBe(true);
            expect(componentRestriction.isAllowedDomain('http://youtube-nocookie.com/embed/')).toBe(true);
            expect(componentRestriction.isAllowedDomain('http://maps.google.com/maps')).toBe(true);
            expect(componentRestriction.isAllowedDomain('http://player.vimeo.com/video/')).toBe(true);
            expect(componentRestriction.isAllowedDomain('http://testdomain.com')).toBe(false);
            expect(componentRestriction.isAllowedDomain('http://fake.domain.com/test')).toBe(false);
        });

        it('check is allowed tags', () => {
            const tags = componentRestriction.getTags($(
                `<div data-test="Test">
                <div>Content</div>
                <span data-action="test">Test</span>
                <div data-image="Image">
                    <img src="#" alt="Alt">
                </div>
            </div>`)[0]);

            expect(componentRestriction.isAllowedTag(tags[0])).toBeFalsy();
            expect(componentRestriction.isAllowedTag(tags[1])).toBeTruthy();
            expect(componentRestriction.isAllowedTag(tags[2])).toBeFalsy();
            expect(componentRestriction.isAllowedTag(tags[3])).toBeTruthy();
            expect(componentRestriction.isAllowedTag(tags[4])).toBeFalsy();
        });

        it('check template', () => {
            expect(componentRestriction.checkTemplate(
                `<div data-test="Test">
                <div>Content</div>
                <span data-action="test">Test</span>
                <div data-image="Image">
                    <img src="#" alt="Alt">
                </div>
            </div>`
            )).toBe(false);

            expect(componentRestriction.checkTemplate(
                `<div data-image="Test">
                <div>Content</div>
                <div id="span">Test</div>
                <div data-image="Test">
                    <table border="0" cellspacing="0"></table>
                </div>
            </div>`
            )).toBe(true);
        });

        it('check template validate', () => {
            expect(componentRestriction.validate(
                `<div data-test="Test">
                <div>Content</div>
                <span data-action="test">Test</span>
                <div data-image="Image">
                    <img src="#" alt="Alt">
                </div>
            </div>`
            )).toEqual(['DIV (data-test)', 'SPAN (data-action)', 'IMG (src, alt)']);

            expect(componentRestriction.validate(
                `<div data-test="Test">
                <div>Content</div>
                <span data-action="test">Test</span>
                <div data-image="Image">
                    <img src="#" alt="Alt">
                </div>
            </div>`, true
            )).toEqual(['<div data-test="Test">', '<span data-action="test">', '<img src="#" alt="Alt">']);

            expect(componentRestriction.validate(
                `<div data-test="Test">
                    <div>Content</div>
                    <span data-action="test">Test</span>
                    <div data-image="Image">
                        <img src="#" alt="Alt">
                    </div>
                </div>
                <div>
                    <picture></picture>
                    <address not-valid></address>
                </div>`
            )).toEqual([
                'DIV (data-test)',
                'SPAN (data-action)',
                'IMG (src, alt)',
                'PICTURE',
                'ADDRESS (not-valid)'
            ]);

            expect(componentRestriction.validate(
                `<div data-test="Test">
                    <div>Content</div>
                    <span data-action="test">Test</span>
                    <div data-image="Image">
                        <img src="#" alt="Alt">
                    </div>
                </div>
                <div>
                    <picture></picture>
                    <address not-valid></address>
                </div>`, true
            )).toEqual([
                '<div data-test="Test">',
                '<span data-action="test">',
                '<img src="#" alt="Alt">',
                'PICTURE',
                '<address not-valid>'
            ]);

            expect(componentRestriction.validate(
                `<div data-test="Test" data-not-allow="Attr">
                <div>Content</div>
                <span data-action="test">Test</span>
                <picture></picture>
                <address not-valid></address>
                <div data-image="Image">
                    <img src="#" alt="Alt">
                </div>
            </div>`
            )).toEqual([
                'DIV (data-test, data-not-allow)',
                'SPAN (data-action)',
                'PICTURE',
                'ADDRESS (not-valid)',
                'IMG (src, alt)'
            ]);

            expect(componentRestriction.validate(
                `<div data-test="Test" data-not-allow="Attr">
                <div>Content</div>
                <span data-action="test">Test</span>
                <picture></picture>
                <address not-valid></address>
                <div data-image="Image">
                    <img src="#" alt="Alt">
                </div>
            </div>`, true
            )).toEqual([
                '<div data-test="Test" data-not-allow="Attr">',
                '<span data-action="test">',
                'PICTURE',
                '<address not-valid>',
                '<img src="#" alt="Alt">'
            ]);

            expect(componentRestriction.validate(
                `<div data-image="Test">
                <div>Content</div>
                <div id="span">Test</div>
                <div data-image="Test">
                    <table border="0" cellspacing="0"></table>
                </div>
            </div>`
            )).toEqual([]);
        });

        it('check normalize tags', () => {
            expect(componentRestriction.normalize(['span', ['data-action']])).toEqual('SPAN (data-action)');
            expect(
                componentRestriction.normalize(['div', ['data-attr1', 'data-attr2']])
            ).toEqual('DIV (data-attr1, data-attr2)');
            expect(componentRestriction.normalize(['table'])).toEqual('TABLE ()');
            expect(componentRestriction.normalize('table')).toEqual('table');
            expect(
                componentRestriction.normalize('<table data-attr=""></table>')
            ).toEqual('<table data-attr></table>');
            expect(
                componentRestriction.normalize(['div', ['data-image'], '<div data-image="Image">'])
            ).toEqual('DIV (<div data-image="Image">)');
        });

        xit('check editor resolve not allowed types', () => {
            expect(componentRestriction.editor.DomComponents.componentTypes.length).toEqual(6);
            expect(componentRestriction.editor.DomComponents.componentTypes.map(({id}) => id)).toEqual(
                ['table', 'comment', 'textnode', 'text', 'wrapper', 'default']
            );
        });
    });
});
